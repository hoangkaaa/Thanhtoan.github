<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test MoMo Payment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
        }
        .payment-option {
            border: 2px solid #ddd;
            padding: 10px;
            margin: 10px 0;
            cursor: pointer;
            text-align: center;
        }
        .payment-option.active {
            border-color: #26551D;
            background-color: #f0f8f0;
        }
        .btn {
            background-color: #26551D;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #1e4419;
        }
        .loading {
            display: none;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>Test Thanh Toán MoMo</h1>
    
    <div>
        <h3>Chọn phương thức thanh toán:</h3>
        <div class="payment-option" data-method="visa">
            <strong>Visa</strong>
        </div>
        <div class="payment-option" data-method="momo">
            <strong>MoMo</strong>
        </div>
        <div class="payment-option" data-method="cod">
            <strong>COD</strong>
        </div>
    </div>

    <div>
        <p><strong>Tổng tiền:</strong> <span id="total-amount">150000</span> VNĐ</p>
    </div>

    <div class="loading" id="loading">
        <p>Đang xử lý...</p>
    </div>

    <button class="btn" id="pay-btn">Thanh Toán</button>

    <div id="result" style="margin-top: 20px;"></div>

    <script>
        let selectedMethod = 'visa';
        
        // Xử lý chọn phương thức thanh toán
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                // Bỏ active tất cả
                document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('active'));
                // Thêm active cho option được chọn
                this.classList.add('active');
                selectedMethod = this.getAttribute('data-method');
                console.log('Selected method:', selectedMethod);
            });
        });

        // Hàm xử lý thanh toán MoMo
        function processMomoPayment(amount) {
            console.log('Processing MoMo payment:', amount);
            document.getElementById('loading').style.display = 'block';
            
            fetch('momo_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'total_amount=' + amount
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);
                    
                    document.getElementById('loading').style.display = 'none';
                    
                    if (data.payUrl) {
                        document.getElementById('result').innerHTML = 
                            '<div style="color: green;">✅ Thành công! Đang chuyển đến MoMo...</div>';
                        setTimeout(() => {
                            window.location.href = data.payUrl;
                        }, 2000);
                    } else {
                        document.getElementById('result').innerHTML = 
                            '<div style="color: red;">❌ Lỗi: ' + (data.message || JSON.stringify(data)) + '</div>';
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    document.getElementById('result').innerHTML = 
                        '<div style="color: red;">❌ Lỗi parse JSON: ' + text + '</div>';
                    document.getElementById('loading').style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                document.getElementById('loading').style.display = 'none';
                document.getElementById('result').innerHTML = 
                    '<div style="color: red;">❌ Lỗi kết nối: ' + error.message + '</div>';
            });
        }

        // Xử lý nút thanh toán
        document.getElementById('pay-btn').addEventListener('click', function() {
            const amount = document.getElementById('total-amount').textContent;
            
            console.log('Pay button clicked');
            console.log('Selected method:', selectedMethod);
            console.log('Amount:', amount);
            
            if (selectedMethod === 'momo') {
                processMomoPayment(amount);
            } else {
                alert('Đây chỉ là test cho ' + selectedMethod + '. Chỉ MoMo được xử lý.');
            }
        });

        // Set default
        document.querySelector('[data-method="momo"]').click();
    </script>
</body>
</html> 