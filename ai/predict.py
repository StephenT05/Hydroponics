import tensorflow as tf
import numpy as np
from tensorflow.keras.preprocessing import image
import sys

# Load trained model
model = tf.keras.models.load_model(
    r"C:\Users\Stephen\Documents\Hydroponics\Hydroponics\ai\plant_model.keras"
)

# Class names
classes = [
    "Healthy",
    "Nitrogen",
    "Phosphorus",
    "Potassium",
    "Zinc"
]

# Get image path
img_path = sys.argv[1]

# Load image
img = image.load_img(img_path, target_size=(96, 96))

# Convert to array
img_array = image.img_to_array(img)

# Add batch dimension
img_array = np.expand_dims(img_array, axis=0)

# Preprocess for MobileNetV2
img_array = tf.keras.applications.mobilenet_v2.preprocess_input(img_array)

# Predict
prediction = model.predict(img_array, verbose=0)

predicted_class = classes[np.argmax(prediction)]

confidence = float(np.max(prediction))

print(f"{predicted_class}|{confidence:.2f}")
