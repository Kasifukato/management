<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Barcode Scanner</title>
    <script src="https://unpkg.com/quagga/dist/quagga.min.js"></script>
    <script>
        function startScanner() {
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
                    alert("Error starting scanner");
                    return;
                }
                Quagga.start();
            });

            Quagga.onDetected(function(data) {
                let barcode = data.codeResult.code;
                alert(`Scanned: ${barcode}`);

                // Send the scanned barcode to the server
                fetch("save_scan.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `barcode=${barcode}`
                }).then(response => response.text())
                  .then(result => console.log(result))
                  .catch(error => console.error("Error:", error));

                Quagga.stop();
            });
        }
    </script>
</head>
<body>
    <h1>Mobile Barcode Scanner</h1>
    <button onclick="startScanner()">Start Scanner</button>
    <div id="scanner-container" style="width: 100%; height: 300px; border: 1px solid black;"></div>
</body>
</html>
