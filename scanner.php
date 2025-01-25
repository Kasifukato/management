<!DOCTYPE html>
<html>
<head>
    <title>Mobile Barcode Scanner</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://unpkg.com/quagga/dist/quagga.min.js"></script>
    <style>
        body { text-align: center; margin-top: 20px; }
        #scanner-container { width: 100%; max-width: 500px; margin: 0 auto; }
        #start-btn, #stop-btn { 
            padding: 10px 20px; 
            margin: 10px; 
            font-size: 16px; 
        }
    </style>
</head>
<body>
    <button id="start-btn" onclick="startScanner()">Start Scanner</button>
    <button id="stop-btn" onclick="stopScanner()" style="display:none;">Stop Scanner</button>
    <div id="scanner-container"></div>

    <script>
        let scannerRunning = false;

        function startScanner() {
            if (scannerRunning) return;

            Quagga.init({
                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: document.querySelector("#scanner-container"),
                    constraints: {
                        facingMode: "environment"
                    }
                },
                decoder: {
                    readers: ["ean_reader", "code_128_reader"]
                }
            }, function(err) {
                if (err) {
                    console.error(err);
                    return;
                }
                Quagga.start();
                scannerRunning = true;
                document.getElementById('start-btn').style.display = 'none';
                document.getElementById('stop-btn').style.display = 'inline-block';
            });

            // Remove any previous onDetected event listener
            Quagga.offDetected();

            // Add the event listener for barcode detection
            Quagga.onDetected(function(result) {
                const barcode = result.codeResult.code;
                
                fetch('scan.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'barcode=' + encodeURIComponent(barcode)
                })
                .then(response => response.text())
                .then(result => {
                    console.log(result);
                    alert('Barcode scanned: ' + barcode);
                    
                    // Optional: Keep scanner running for multiple scans
                    // stopScanner();
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        }

        function stopScanner() {
            if (scannerRunning) {
                Quagga.stop();
                scannerRunning = false;
                document.getElementById('start-btn').style.display = 'inline-block';
                document.getElementById('stop-btn').style.display = 'none';
            }
        }
    </script>
</body>
</html>
