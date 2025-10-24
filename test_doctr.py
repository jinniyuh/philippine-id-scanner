from doctr.io import DocumentFile
from doctr.models import ocr_predictor

# Load pre-trained model
model = ocr_predictor(pretrained=True)

# Load a sample image
doc = DocumentFile.from_images("sample.jpg")  # <-- replace with your test image

# Run OCR
result = model(doc)

# Print the detected text
for page in result.export()['pages']:
    for block in page['blocks']:
        for line in block['lines']:
            print(' '.join([word['value'] for word in line['words']]))
