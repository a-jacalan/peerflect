<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Custom Notification Modal</title>
    <style>
        #customModal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fefefe;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #888;
            width: 300px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .modal-content.success {
            border-left: 5px solid green;
            background-color: #dff0d8;
            color: #3c763d;
        }

        .modal-content.error {
            border-left: 5px solid red;
            background-color: #f2dede;
            color: #a94442;
        }

        .modal-content.warning {
            border-left: 5px solid orange;
            background-color: #fcf8e3;
            color: #8a6d3b;
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
        }

        .modal-buttons button {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .modal-buttons .confirm {
            background-color: #5cb85c;
            color: white;
        }

        .modal-buttons .cancel {
            background-color: #d9534f;
            color: white;
        }
    </style>
</head>
<body>
    <div id="customModal">
        <div class="modal-content">
            <div id="modalMessage"></div>
            <div class="modal-buttons">
                <button class="confirm" id="modalConfirm">Confirm</button>
                <button class="cancel" id="modalCancel">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        function showModal(options) {
            const modal = document.getElementById('customModal');
            const messageEl = document.getElementById('modalMessage');
            const confirmBtn = document.getElementById('modalConfirm');
            const cancelBtn = document.getElementById('modalCancel');

            // Reset previous state
            modal.style.display = 'flex';
            messageEl.textContent = options.message || '';
            modal.querySelector('.modal-content').className = 'modal-content ' + (options.type || '');

            // Configure buttons based on options
            if (options.showConfirmButtons === false) {
                confirmBtn.style.display = 'none';
                cancelBtn.style.display = 'none';
            } else {
                confirmBtn.style.display = 'inline-block';
                cancelBtn.style.display = 'inline-block';

                // Reset previous event listeners
                confirmBtn.onclick = null;
                cancelBtn.onclick = null;

                // Set new event listeners
                confirmBtn.onclick = function() {
                    modal.style.display = 'none';
                    if (options.onConfirm) options.onConfirm();
                };

                cancelBtn.onclick = function() {
                    modal.style.display = 'none';
                    if (options.onCancel) options.onCancel();
                };
            }

            // Auto-close for notifications
            if (options.type && options.type !== 'warning') {
                setTimeout(() => {
                    modal.style.display = 'none';
                }, options.duration || 3000);
            }
        }

        // Example usage:
        // showModal({
        //     message: 'Are you sure you want to delete this post?',
        //     type: 'warning',
        //     onConfirm: () => { /* delete action */ },
        //     onCancel: () => { /* cancel action */ }
        // });
    </script>
</body>
</html>