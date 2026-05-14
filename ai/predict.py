import sys
import os
import numpy as np
import tensorflow as tf

from tensorflow.keras.utils import load_img, img_to_array
from tensorflow.keras.applications.efficientnet import preprocess_input

# =========================
# SETTINGS
# =========================

IMG_SIZE = (224, 224)

CLASS_NAMES = [
    "Healthy",
    "Nitrogen",
    "Phosphorus",
    "Potassium",
]

UNKNOWN_THRESHOLD = 0.45

# =========================
# LOAD MODEL (use script-relative path so Laravel can invoke predict.py)
# =========================

# Resolve model path relative to this script file
script_dir = os.path.dirname(os.path.realpath(__file__))
model_path = os.path.join(script_dir, 'plant_model.keras')

if not os.path.exists(model_path):
    raise ValueError(f"File not found: filepath={model_path}. Please ensure the file is an accessible `.keras` zip file.")

model = tf.keras.models.load_model(model_path)

# =========================
# LOAD & PREPROCESS IMAGE
# =========================

if len(sys.argv) < 2:
    print("Usage: python predict_lettuce_npk.py <image_path>")
    sys.exit(1)

image_path = sys.argv[1]

image = load_img(image_path, target_size=IMG_SIZE)

image_array = img_to_array(image)           # shape: (224, 224, 3)
image_array = np.expand_dims(image_array, axis=0)  # shape: (1, 224, 224, 3)
image_array = preprocess_input(image_array) # EfficientNet preprocessing

# =========================
# PREDICT
# =========================

predictions = model.predict(image_array, verbose=0)[0]  # shape: (num_classes,)

predicted_index = int(np.argmax(predictions))
confidence     = float(predictions[predicted_index])

# All class confidences for transparency
all_confidences = {
    CLASS_NAMES[i]: float(predictions[i])
    for i in range(len(CLASS_NAMES))
}

# =========================
# CONFIDENCE THRESHOLD
# =========================

if confidence < UNKNOWN_THRESHOLD:
    prediction = "Unknown"
else:
    prediction = CLASS_NAMES[predicted_index]

# =========================
# OUTPUT
# =========================

print(f"{prediction}|{confidence:.2f}")

# Optional: uncomment to see full confidence breakdown
# for cls, conf in all_confidences.items():
#     print(f"  {cls}: {conf:.2%}")