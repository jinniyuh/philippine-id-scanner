/**
 * Barcode Scanner JavaScript
 * Handles barcode scanning functionality for the Bago City Veterinary IMS
 */

class BarcodeScanner {
    constructor() {
        this.scannerActive = false;
        this.video = null;
        this.canvas = null;
        this.context = null;
        this.stream = null;
    }

    /**
     * Initialize the barcode scanner
     */
    async init() {
        try {
            // Get video element
            this.video = document.getElementById('barcode-video');
            this.canvas = document.getElementById('barcode-canvas');
            this.context = this.canvas.getContext('2d');

            if (!this.video || !this.canvas) {
                throw new Error('Required elements not found');
            }

            // Request camera access
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment', // Use back camera if available
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            });

            this.video.srcObject = this.stream;
            this.video.play();

            // Set canvas dimensions to match video
            this.video.addEventListener('loadedmetadata', () => {
                this.canvas.width = this.video.videoWidth;
                this.canvas.height = this.video.videoHeight;
            });

            return true;
        } catch (error) {
            console.error('Error initializing barcode scanner:', error);
            this.showError('Camera access denied or not available. Please use file upload instead.');
            return false;
        }
    }

    /**
     * Start scanning for barcodes
     */
    startScanning() {
        if (!this.video || !this.canvas) {
            this.showError('Scanner not initialized');
            return;
        }

        this.scannerActive = true;
        this.scanFrame();
    }

    /**
     * Stop scanning
     */
    stopScanning() {
        this.scannerActive = false;
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
        }
    }

    /**
     * Scan current frame for barcodes
     */
    scanFrame() {
        if (!this.scannerActive || !this.video || this.video.readyState !== 4) {
            if (this.scannerActive) {
                requestAnimationFrame(() => this.scanFrame());
            }
            return;
        }

        // Draw current frame to canvas
        this.context.drawImage(this.video, 0, 0, this.canvas.width, this.canvas.height);

        // Get image data for barcode detection
        const imageData = this.context.getImageData(0, 0, this.canvas.width, this.canvas.height);
        
        // Convert to blob for upload
        this.canvas.toBlob((blob) => {
            this.processBarcodeImage(blob);
        }, 'image/jpeg', 0.8);

        // Continue scanning
        if (this.scannerActive) {
            requestAnimationFrame(() => this.scanFrame());
        }
    }

    /**
     * Process barcode image
     */
    async processBarcodeImage(blob) {
        try {
            const formData = new FormData();
            formData.append('barcode_image', blob, 'barcode_scan.jpg');

            const response = await fetch('barcode_handler.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.handleScanSuccess(result);
            } else if (result.error && !result.error.includes('No barcode found')) {
                // Only show error if it's not a "no barcode found" error
                console.log('Scan attempt:', result.error);
            }
        } catch (error) {
            console.error('Error processing barcode:', error);
        }
    }

    /**
     * Handle successful barcode scan
     */
    handleScanSuccess(result) {
        this.stopScanning();
        
        // Auto-fill form fields
        this.autoFillForm(result);
        
        // Show success message
        this.showSuccess(`Barcode scanned successfully! Resident: ${result.name} from ${result.barangay}`);
        
        // Hide scanner
        this.hideScanner();
    }

    /**
     * Auto-fill form fields with scanned data
     */
    autoFillForm(data) {
        // Fill name field
        const nameField = document.getElementById('fullname') || document.getElementById('name');
        if (nameField && data.name) {
            nameField.value = data.name;
            nameField.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Fill barangay field
        const barangayField = document.getElementById('barangay') || document.getElementById('address');
        if (barangayField && data.barangay) {
            barangayField.value = data.barangay;
            barangayField.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Set city and province (if fields exist)
        const cityField = document.getElementById('city');
        if (cityField) {
            cityField.value = data.city || 'Bago City';
        }

        const provinceField = document.getElementById('province');
        if (provinceField) {
            provinceField.value = data.province || 'Negros Occidental';
        }
    }

    /**
     * Show scanner interface
     */
    showScanner() {
        const scannerModal = document.getElementById('barcode-scanner-modal');
        if (scannerModal) {
            scannerModal.style.display = 'block';
            this.init().then(success => {
                if (success) {
                    this.startScanning();
                }
            });
        }
    }

    /**
     * Hide scanner interface
     */
    hideScanner() {
        const scannerModal = document.getElementById('barcode-scanner-modal');
        if (scannerModal) {
            scannerModal.style.display = 'none';
        }
        this.stopScanning();
    }

    /**
     * Handle file upload for barcode scanning
     */
    async handleFileUpload(file) {
        if (!file) {
            this.showError('No file selected');
            return;
        }

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            this.showError('Invalid file type. Please select a JPEG, PNG, or GIF image.');
            return;
        }

        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            this.showError('File size too large. Maximum 5MB allowed.');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('barcode_image', file);

            const response = await fetch('barcode_handler.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.handleScanSuccess(result);
            } else {
                this.showError(result.error || 'Failed to scan barcode');
            }
        } catch (error) {
            console.error('Error uploading file:', error);
            this.showError('Error uploading file for scanning');
        }
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        this.showMessage(message, 'success');
    }

    /**
     * Show error message
     */
    showError(message) {
        this.showMessage(message, 'error');
    }

    /**
     * Show message to user
     */
    showMessage(message, type) {
        // Create or update message element
        let messageEl = document.getElementById('barcode-message');
        if (!messageEl) {
            messageEl = document.createElement('div');
            messageEl.id = 'barcode-message';
            messageEl.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 5px;
                color: white;
                font-weight: bold;
                z-index: 10000;
                max-width: 400px;
                word-wrap: break-word;
            `;
            document.body.appendChild(messageEl);
        }

        messageEl.textContent = message;
        messageEl.style.backgroundColor = type === 'success' ? '#28a745' : '#dc3545';
        messageEl.style.display = 'block';

        // Auto-hide after 5 seconds
        setTimeout(() => {
            messageEl.style.display = 'none';
        }, 5000);
    }
}

// Initialize scanner when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.barcodeScanner = new BarcodeScanner();
    
    // Add event listeners for scanner buttons
    const scanButton = document.getElementById('scan-barcode-btn');
    if (scanButton) {
        scanButton.addEventListener('click', function() {
            window.barcodeScanner.showScanner();
        });
    }

    const closeScannerBtn = document.getElementById('close-scanner-btn');
    if (closeScannerBtn) {
        closeScannerBtn.addEventListener('click', function() {
            window.barcodeScanner.hideScanner();
        });
    }

    // Handle file upload
    const fileInput = document.getElementById('barcode-file-input');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                window.barcodeScanner.handleFileUpload(e.target.files[0]);
            }
        });
    }
});
