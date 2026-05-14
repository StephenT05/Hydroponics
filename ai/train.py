import numpy as np
import tensorflow as tf
from tensorflow.keras import layers, models
from tensorflow.keras.applications import EfficientNetB0
from tensorflow.keras.applications.efficientnet import preprocess_input
from tensorflow.keras.callbacks import EarlyStopping, ReduceLROnPlateau

# =========================
# SETTINGS
# =========================

IMG_SIZE    = (224, 224)
BATCH_SIZE  = 8           # smaller = more gradient updates per epoch
EPOCHS      = 50
DATASET_PATH = "dataset"

# =========================
# LOAD DATASET
# =========================

train_dataset = tf.keras.utils.image_dataset_from_directory(
    DATASET_PATH,
    validation_split=0.2,
    subset="training",
    seed=123,
    image_size=IMG_SIZE,
    batch_size=BATCH_SIZE,
)

val_dataset = tf.keras.utils.image_dataset_from_directory(
    DATASET_PATH,
    validation_split=0.2,
    subset="validation",
    seed=123,
    image_size=IMG_SIZE,
    batch_size=BATCH_SIZE,
)

class_names = train_dataset.class_names
num_classes = len(class_names)
print("Classes:", class_names)

# =========================
# CLASS WEIGHTS
# (handles the heavy FN vs K imbalance)
# =========================

# Count samples per class by iterating labels before caching
label_counts = np.zeros(num_classes, dtype=np.int64)
for _, labels in train_dataset:
    for label in labels.numpy():
        label_counts[label] += 1

print("Samples per class:", dict(zip(class_names, label_counts)))

total_samples = label_counts.sum()
class_weights = {
    i: total_samples / (num_classes * count)
    for i, count in enumerate(label_counts)
}
print("Class weights:", class_weights)

# =========================
# PERFORMANCE OPTIMIZATION
# =========================

AUTOTUNE = tf.data.AUTOTUNE

train_dataset = train_dataset.cache().shuffle(1000).prefetch(buffer_size=AUTOTUNE)
val_dataset   = val_dataset.cache().prefetch(buffer_size=AUTOTUNE)

# =========================
# DATA AUGMENTATION
# (aggressive — critical for a ~200-image dataset)
# =========================

data_augmentation = models.Sequential([
    layers.RandomFlip("horizontal_and_vertical"),
    layers.RandomRotation(0.3),
    layers.RandomZoom(0.2),
    layers.RandomContrast(0.3),
    layers.RandomBrightness(0.2),
    layers.RandomTranslation(height_factor=0.1, width_factor=0.1),
], name="augmentation")

# =========================
# BASE MODEL — EfficientNetB0
# (better generalisation than MobileNetV2 on small datasets)
# =========================

base_model = EfficientNetB0(
    input_shape=(224, 224, 3),
    include_top=False,
    weights="imagenet",
)
base_model.trainable = False   # frozen for Phase 1

# =========================
# BUILD MODEL
# =========================

inputs = tf.keras.Input(shape=(224, 224, 3))
x = data_augmentation(inputs)
x = preprocess_input(x)
x = base_model(x, training=False)
x = layers.GlobalAveragePooling2D()(x)
x = layers.BatchNormalization()(x)
x = layers.Dropout(0.3)(x)          # 0.3 instead of 0.5 — less aggressive
outputs = layers.Dense(num_classes, activation="softmax")(x)

model = tf.keras.Model(inputs, outputs)

# =========================
# CALLBACKS
# =========================

early_stop = EarlyStopping(
    monitor="val_loss",
    patience=5,                  # more patience alongside LR decay
    restore_best_weights=True,
)

lr_scheduler = ReduceLROnPlateau(
    monitor="val_loss",
    factor=0.5,
    patience=2,
    min_lr=1e-7,
    verbose=1,
)

# =========================
# PHASE 1 — Train head only
# (protects pretrained weights while the new head learns)
# =========================

print("\n--- Phase 1: Training head (backbone frozen) ---")

model.compile(
    optimizer=tf.keras.optimizers.Adam(learning_rate=1e-3),
    loss="sparse_categorical_crossentropy",
    metrics=["accuracy"],
)

model.fit(
    train_dataset,
    validation_data=val_dataset,
    epochs=15,
    callbacks=[early_stop, lr_scheduler],
    class_weight=class_weights,
)

# =========================
# PHASE 2 — Fine-tune top layers
# (unfreeze last 40 layers of backbone)
# =========================

print("\n--- Phase 2: Fine-tuning top backbone layers ---")

base_model.trainable = True
for layer in base_model.layers[:-40]:
    layer.trainable = False

# Lower LR to avoid destroying pretrained weights
model.compile(
    optimizer=tf.keras.optimizers.Adam(learning_rate=1e-5),
    loss="sparse_categorical_crossentropy",
    metrics=["accuracy"],
)

early_stop_ft = EarlyStopping(
    monitor="val_loss",
    patience=7,
    restore_best_weights=True,
)

lr_scheduler_ft = ReduceLROnPlateau(
    monitor="val_loss",
    factor=0.5,
    patience=3,
    min_lr=1e-8,
    verbose=1,
)

history = model.fit(
    train_dataset,
    validation_data=val_dataset,
    epochs=EPOCHS,
    callbacks=[early_stop_ft, lr_scheduler_ft],
    class_weight=class_weights,
)

# =========================
# EVALUATE
# =========================

print("\n--- Evaluation on validation set ---")
loss, acc = model.evaluate(val_dataset)
print(f"Val Loss: {loss:.4f} | Val Accuracy: {acc:.4f}")

# Per-class accuracy
print("\n--- Per-class predictions ---")
y_true, y_pred = [], []
for images, labels in val_dataset:
    preds = model.predict(images, verbose=0)
    y_true.extend(labels.numpy())
    y_pred.extend(np.argmax(preds, axis=1))

y_true = np.array(y_true)
y_pred = np.array(y_pred)

for i, name in enumerate(class_names):
    mask = y_true == i
    if mask.sum() > 0:
        cls_acc = (y_pred[mask] == i).mean()
        print(f"  {name}: {cls_acc:.2%}  ({mask.sum()} samples)")

# =========================
# SAVE MODEL
# =========================

model.save("plant_model.keras")
print("\nModel saved as plant_model.keras")