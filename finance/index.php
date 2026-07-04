<?php
session_start();
// NOTE: If your database connection logic is NOT included in financedb.php, 
// you should include it here, e.g., include 'connect.php'; 

if (!isset($_SESSION['user'])) {
    // Redirect to login if the user is not logged in
    header("Location: index.php");
    exit;
}

// Assuming the session variable 'user' holds an array with at least 'firstName' and 'lastName'
$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Finance Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>

    <style>
        /* --- General Desktop Styles --- */
        body {
            background-color: #f4f6f9; /* Light, calming background */
        }
        /* Core Calendar Grid (Desktop) */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr); /* 7 columns for 7 days */
            gap: 5px;
            border-top: 1px solid #eee;
        }

        /* Day Cells (Desktop) */
        .day-cell {
            padding: 10px;
            height: 80px; /* Uniform height for visual consistency */
            border: 1px solid #f8f9fa; /* Light border */
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s;
            background-color: #ffffff;
            position: relative;
        }

        .day-cell:hover {
            background-color: #f0f8ff; /* Light hover effect */
        }

        /* Financial Status Indicator (UX feature) */
        .expense-indicator {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .indicator-high { background-color: #dc3545; } /* Red */
        .indicator-low { background-color: #ffc107; } /* Yellow */
        .indicator-none { background-color: #28a745; } /* Green/None */
        
        /* Modal Colors for easy recognition */
        #transactionModal .modal-header.expense {
            background-color: #dc3545;
            color: white;
        }
        #transactionModal .modal-header.income {
            background-color: #28a745;
            color: white;
        }
        
        /* Button for switching type */
        .modal-type-switcher button {
            flex-grow: 1;
            margin: 0 5px;
        }

        /* === FIXED PROFILE STYLES (BOTTOM-RIGHT CORNER) === */
        .profile-fixed-container {
            position: fixed; /* Makes the element stay put, even when scrolling */
            bottom: 20px;   /* Positions it 20 pixels from the bottom edge */
            right: 20px;    /* Positions it 20 pixels from the right edge */
            z-index: 1000;   /* Ensures it sits on top of all other content */
            width: 200px; /* Example fixed width */
            text-align: center;
        }

        .profile-fixed-container .profile { 
            background: rgba(18, 22, 36, 0.9); /* Semi-transparent dark background */
            border: 1px solid #ff00aa; 
            border-radius: 15px; 
            padding: 15px; 
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            color: #fff;
        }
        
        /* NEW: Style for the real-time clock */
        #liveClock {
            font-family: monospace; /* Digital/Segmented look */
            font-weight: bold;
            font-size: 1.5rem; /* H4 size */
            padding: 5px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .profile-fixed-container .profile h2 {
            font-size: 16px; 
            margin: 0;
            color: #fff;
        }

        .profile-fixed-container .profile .salute {
            font-size: 12px;
            color: #ccc;
        }
        /* ================================================== */


        /* === MOBILE RESPONSIVENESS (Media Query) === */
        @media (max-width: 768px) {
            
            /* Header: Stack elements vertically for small screens */
            header {
                flex-direction: column;
                gap: 10px; /* Space between stacked buttons/text */
            }
            /* Make the month buttons full width */
            header button {
                width: 100%;
                margin: 5px 0;
            }

            /* Calendar Grid: Adjust for mobile viewing */
            .calendar-grid {
                /* Change from 7 columns to just 1 column for days, and keep 7 headers */
                grid-template-columns: repeat(7, 1fr); 
                gap: 1px;
            }
            
            /* Make sure week day headers are still visible but smaller */
            .calendar-grid .fw-bold {
                font-size: 0.75rem;
            }

            /* Day Cells: Reduce height and padding to fit more content */
            .day-cell {
                height: 45px; /* Shorter cells */
                padding: 5px;
                font-size: 0.8rem;
            }

            /* Center the day number for better visibility in a small box */
            .day-cell .h5 {
                font-size: 1rem;
                text-align: center !important;
            }

            /* Hide the detailed dollar amount text on calendar days to save space */
            .day-cell small {
                display: none !important;
            }
            
            /* Ensure the profile container doesn't take up too much space */
            .profile-fixed-container {
                width: 150px;
                right: 10px;
                bottom: 10px;
            }

            .profile-fixed-container .profile h2 {
                font-size: 14px;
            }
            
            /* Adjust clock size for mobile */
            #liveClock {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>

    <div class="container-fluid p-3">
        <header class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white shadow-sm rounded">
            <a href="../account.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            <div class="d-flex justify-content-between align-items-center w-50" id="monthNav">
                <button id="prevMonth" class="btn btn-outline-secondary">&leftarrow;</button>
                <h1 id="currentMonthYear" class="h3 mb-0 text-center">Loading...</h1>
                <button id="nextMonth" class="btn btn-outline-secondary">&rightarrow;</button>
            </div>
            <button class="btn btn-primary" id="openTransactionModalBtn" data-bs-toggle="modal" data-bs-target="#transactionModal">+ Add Transaction</button>
        </header>

        <div class="row">
            <div class="col-lg-8">
                <div class="card p-3 mb-4">
                    <h2 class="card-title h4">Monthly Calendar</h2>
                    <div id="calendarGrid" class="calendar-grid">
                        </div>
                </div>

                <div class="card p-4">
                    <h2 class="h4">Monthly Performance</h2>
                    <div id="summaryDashboard" class="row mb-4">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="p-3 bg-success-subtle text-success rounded">
                                <small>Total Income (LKR)</small><h5 id="totalIncome">LKR 0.00</h5>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="p-3 bg-danger-subtle text-danger rounded">
                                <small>Total Expenses (LKR)</small><h5 id="totalExpenses">LKR 0.00</h5>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-info-subtle text-primary rounded">
                                <small>Net Balance (LKR)</small><h5 id="netBalance">LKR 0.00</h5>
                            </div>
                        </div>
                    </div>
                    
                    <h2 class="h4">Expense Distribution</h2>
                    <canvas id="expenseChart" class="w-100" style="max-height: 300px;"></canvas>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card p-3 mb-4 bg-light">
                    <h2 class="h5 text-primary">💡 Savings Suggestions</h2>
                    <ul id="savingsTips" class="list-group list-group-flush">
                        <li class="list-group-item bg-light">Data will generate personalized tips here.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="transactionModal" tabindex="-1" aria-labelledby="transactionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header expense" id="modalHeader">
                    <h5 class="modal-title" id="transactionModalLabel">Add New Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="transactionForm">
                    <div class="modal-body">
                        
                        <div class="mb-3 d-flex modal-type-switcher">
                            <button type="button" class="btn btn-outline-danger active" id="switchExpense">Expense</button>
                            <button type="button" class="btn btn-outline-success" id="switchIncome">Income</button>
                        </div>

                        <input type="hidden" id="transactionType" name="type" value="expense">
                        
                        <div class="mb-3">
                            <label for="transactionDate" class="form-label">Date</label>
                            <input type="date" class="form-control" id="transactionDate" name="date" required>
                        </div>

                        <div class="mb-3">
                            <label for="transactionAmount" class="form-label">Amount (LKR)</label>
                            <input type="number" step="0.01" class="form-control" id="transactionAmount" name="amount" required>
                        </div>
                        
                        <div class="mb-3" id="categoryField">
                            <label for="transactionCategory" class="form-label">Category</label>
                            <select class="form-select" id="transactionCategory" name="category" required>
                                </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="transactionDescription" class="form-label">Description (Optional)</label>
                            <input type="text" class="form-control" id="transactionDescription" name="description">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="saveButton">Save Transaction</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="profile-fixed-container">
        <div class="profile">
            <div id="liveClock" class="mb-2 text-warning">--:--:--</div>
            
            <h2><?= htmlspecialchars($user['firstName'].' '.$user['lastName']) ?></h2>
            <div class="salute">Hello, <?= htmlspecialchars($user['firstName']) ?> 👋</div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const BACKEND_FILE = 'financedb.php'; 
        
        // Define the currency symbol once for consistency
        const CURRENCY = 'LKR'; 
        // Define the high expense threshold in LKR (e.g., 10,000 LKR)
        const HIGH_EXPENSE_THRESHOLD = 10000; 

        const calendarGrid = document.getElementById('calendarGrid');
        const currentMonthYear = document.getElementById('currentMonthYear');
        const transactionTypeInput = document.getElementById('transactionType');
        const modalHeader = document.getElementById('modalHeader');
        const transactionModalLabel = document.getElementById('transactionModalLabel');
        const transactionCategorySelect = document.getElementById('transactionCategory');
        const transactionForm = document.getElementById('transactionForm');
        const saveButton = document.getElementById('saveButton');
        const switchExpenseBtn = document.getElementById('switchExpense');
        const switchIncomeBtn = document.getElementById('switchIncome');


        let currentDate = new Date();
        let transactionData = [];
        let expenseChartInstance;

        const expenseCategories = ['Rent', 'Tuition/Books', 'Groceries', 'Dining Out', 'Transport', 'Utilities', 'Social/Fun', 'Health', 'Other'];
        const incomeCategories = ['Job/Salary', 'Scholarship', 'Loan', 'Parents/Gift', 'Other Income'];

        // Helper function to format amount with currency
        function formatCurrency(amount) {
            // Using toLocaleString for better formatting, assuming en-LK or standard formatting
            return `${CURRENCY} ${parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")}`; 
        }

        // --- Data Population Helper ---
        function populateCategories(type) {
            transactionCategorySelect.innerHTML = '<option value="" disabled selected>Select Category</option>';
            const categories = type === 'expense' ? expenseCategories : incomeCategories;
            categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category;
                option.textContent = category;
                transactionCategorySelect.appendChild(option);
            });
            document.getElementById('categoryField').style.display = 'block'; 
        }

        // --- UI/UX Helper Functions ---
        function updateModalHeader(type) {
            if (type === 'expense') {
                modalHeader.className = 'modal-header expense';
                transactionModalLabel.textContent = 'Add New Expense';
                saveButton.className = 'btn btn-danger';
                switchExpenseBtn.classList.add('active');
                switchIncomeBtn.classList.remove('active');
            } else {
                modalHeader.className = 'modal-header income';
                transactionModalLabel.textContent = 'Add New Income';
                saveButton.className = 'btn btn-success';
                switchIncomeBtn.classList.add('active');
                switchExpenseBtn.classList.remove('active');
            }
        }
        
        // --- 1. Calendar Rendering Function ---
        function renderCalendar() {
            calendarGrid.innerHTML = '';
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const firstDayOfMonth = new Date(year, month, 1).getDay(); // 0 = Sunday
            
            currentMonthYear.textContent = currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

            const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

            // 1a. Weekday Headers
            weekdays.forEach(day => {
                const header = document.createElement('div');
                header.textContent = day;
                header.className = 'text-center fw-bold py-2 text-secondary';
                calendarGrid.appendChild(header);
            });

            // 1b. Empty Cells for alignment
            for (let i = 0; i < firstDayOfMonth; i++) {
                const emptyCell = document.createElement('div');
                emptyCell.className = 'day-cell border-0 bg-transparent';
                calendarGrid.appendChild(emptyCell);
            }

            // 1c. Day Cells
            for (let i = 1; i <= daysInMonth; i++) {
                const dayCell = document.createElement('div');
                
                const dayDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                dayCell.setAttribute('data-date', dayDate);
                
                const dailyData = transactionData
                    .filter(t => t.date === dayDate)
                
                const dailyExpense = dailyData
                    .filter(t => t.type === 'expense')
                    .reduce((sum, t) => sum + parseFloat(t.amount), 0);
                
                const dailyIncome = dailyData
                    .filter(t => t.type === 'income')
                    .reduce((sum, t) => sum + parseFloat(t.amount), 0);


                let indicatorClass = 'indicator-none';
                let indicatorText = '';

                // Use the LKR threshold
                if (dailyExpense > HIGH_EXPENSE_THRESHOLD) {
                    indicatorClass = 'indicator-high';
                } else if (dailyExpense > 0) {
                    indicatorClass = 'indicator-low';
                }
                
                if (dailyExpense > 0 && dailyIncome > 0) {
                    // Use LKR formatting
                    indicatorText = `Net: ${formatCurrency(dailyIncome - dailyExpense)}`; 
                    indicatorClass = dailyIncome >= dailyExpense ? 'indicator-none' : 'indicator-high';
                } else if (dailyExpense > 0) {
                    // Use LKR formatting
                    indicatorText = `-${formatCurrency(dailyExpense)}`;
                    indicatorClass = dailyExpense > HIGH_EXPENSE_THRESHOLD ? 'indicator-high' : 'indicator-low';
                } else if (dailyIncome > 0) {
                    // Use LKR formatting
                    indicatorText = `+${formatCurrency(dailyIncome)}`;
                    indicatorClass = 'indicator-none';
                }


                dayCell.className = 'day-cell text-end';
                dayCell.innerHTML = `
                    <div class="h5 mb-0">${i}</div>
                    <div class="expense-indicator ${indicatorClass}"></div>
                    <small class="d-block text-start text-muted">${indicatorText}</small>
                `;
                
                dayCell.addEventListener('click', function() {
                    document.getElementById('transactionDate').value = this.dataset.date;
                    
                    transactionTypeInput.value = 'expense';
                    populateCategories('expense');
                    updateModalHeader('expense');
                    
                    // Reset fields for new entry
                    document.getElementById('transactionAmount').value = '';
                    document.getElementById('transactionDescription').value = '';
                    
                    new bootstrap.Modal(document.getElementById('transactionModal')).show();
                });

                calendarGrid.appendChild(dayCell);
            }
            
            updateSummaryDashboard();
        }

        // --- 2. Summary Dashboard & Graph Updates ---
        function updateSummaryDashboard() {
            // Filter transactions for the current month only 
            const currentMonthTransactions = transactionData.filter(t => {
                const tDate = new Date(t.date);
                // The transaction dates should already be filtered by the backend for the current month
                // This check is mainly for robustness if the API returns a wider range
                return tDate.getMonth() === currentDate.getMonth() && tDate.getFullYear() === currentDate.getFullYear();
            });

            const totalIncome = currentMonthTransactions
                .filter(t => t.type === 'income')
                .reduce((sum, t) => sum + parseFloat(t.amount), 0);
            
            const totalExpenses = currentMonthTransactions
                .filter(t => t.type === 'expense')
                .reduce((sum, t) => sum + parseFloat(t.amount), 0);
            
            const netBalance = totalIncome - totalExpenses;

            // Use LKR formatting
            document.getElementById('totalIncome').textContent = formatCurrency(totalIncome);
            document.getElementById('totalExpenses').textContent = formatCurrency(totalExpenses);
            document.getElementById('netBalance').textContent = formatCurrency(netBalance);

            const netBalanceDiv = document.getElementById('netBalance').closest('.p-3');
            netBalanceDiv.classList.remove('bg-success-subtle', 'bg-danger-subtle', 'text-success', 'text-danger', 'text-primary', 'bg-info-subtle');
            
            if (netBalance > 0) {
                netBalanceDiv.classList.add('bg-success-subtle', 'text-success');
            } else if (netBalance < 0) {
                netBalanceDiv.classList.add('bg-danger-subtle', 'text-danger');
            } else {
                netBalanceDiv.classList.add('bg-info-subtle', 'text-primary'); // Default for zero
            }

            updateExpenseChart(currentMonthTransactions.filter(t => t.type === 'expense'));
            generateSavingsTips(totalExpenses, currentMonthTransactions.filter(t => t.type === 'expense'));
        }

        function updateExpenseChart(expenses) {
            const categorySums = expenses.reduce((acc, t) => {
                acc[t.category] = (acc[t.category] || 0) + parseFloat(t.amount);
                return acc;
            }, {});

            const labels = Object.keys(categorySums).filter(c => categorySums[c] > 0);
            const dataValues = labels.map(c => categorySums[c]);

            const chartData = {
                labels: labels,
                datasets: [{
                    data: dataValues,
                    backgroundColor: [
                        '#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#dc3545', 
                        '#fd7e14', '#ffc107', '#28a745', '#20c997'
                    ],
                }]
            };

            const ctx = document.getElementById('expenseChart').getContext('2d');
            
            if (expenseChartInstance) {
                expenseChartInstance.destroy();
            }
            
            if (dataValues.length > 0) {
                expenseChartInstance = new Chart(ctx, {
                    type: 'doughnut',
                    data: chartData,
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'right',
                            },
                            title: {
                                display: true,
                                text: 'Expense Breakdown (LKR)' // Update chart title
                            }
                        }
                    }
                });
            } else {
                // Clear and display a message when no data is present
                ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
                ctx.font = "16px Arial";
                ctx.fillStyle = "#6c757d";
                ctx.textAlign = "center";
                ctx.fillText("No expense data for this month.", ctx.canvas.width / 2, ctx.canvas.height / 2);
            }
        }
        
        // --- 4. Savings Suggestions ---
        function generateSavingsTips(totalExpenses, expenses) {
            const tipsList = document.getElementById('savingsTips');
            tipsList.innerHTML = '';

            if (expenses.length === 0) {
                 tipsList.innerHTML = '<li class="list-group-item bg-light">No expenses recorded yet. Start tracking to get personalized tips!</li>';
                 return;
            }

            const categorySums = expenses.reduce((acc, t) => {
                acc[t.category] = (acc[t.category] || 0) + parseFloat(t.amount);
                return acc;
            }, {});

            // Find the top spending category
            let maxCategory = null;
            let maxAmount = 0;
            for (const category in categorySums) {
                if (categorySums[category] > maxAmount) {
                    maxAmount = categorySums[category];
                    maxCategory = category;
                }
            }
            
            // Tip 1: Top Spending Category Alert
            if (maxCategory && maxAmount > totalExpenses * 0.15) { // Tip if category is > 15% of total expense
                const percentage = (maxAmount / totalExpenses * 100).toFixed(0);
                const tip = document.createElement('li');
                tip.className = 'list-group-item bg-light text-danger';
                tip.innerHTML = `📈 **Major Spending Alert:** **${maxCategory}** accounts for **${percentage}%** of your total expenses! Consider a strict budget here.`;
                tipsList.appendChild(tip);
            }

            // Tip 2: High Dining Out (Using a 15,000 LKR threshold)
            if (categorySums['Dining Out'] && categorySums['Dining Out'] > 15000) { 
                const tip = document.createElement('li');
                tip.className = 'list-group-item bg-light';
                tip.innerHTML = `🍽️ Try cooking at home! Your **Dining Out** expenses (${formatCurrency(categorySums['Dining Out'])}) are a large part of your budget this month.`;
                tipsList.appendChild(tip);
            }
            
            // Tip 3: Uncategorized spending (Using a 7,500 LKR threshold)
            if (categorySums['Other'] && categorySums['Other'] > 7500) {
                const tip = document.createElement('li');
                tip.className = 'list-group-item bg-light text-warning';
                tip.innerHTML = `⚠️ High **'Other'** spending (${formatCurrency(categorySums['Other'])}). Try to categorize these items better for clearer insight.`;
                tipsList.appendChild(tip);
            }
        }

        // --- 3. AJAX Data Fetching (Get Monthly Data) ---
        function fetchMonthlyData() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth() + 1; // JS month is 0-11, DB expects 1-12

            const formData = new FormData();
            formData.append('action', 'getMonthlyData');
            formData.append('year', year);
            formData.append('month', month);

            fetch(BACKEND_FILE, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    if (response.status === 401) {
                         alert('Session expired or unauthorized. Please log in again.');
                    }
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    transactionData = data.transactions; 
                    renderCalendar();
                } else {
                    console.error('Backend Error:', data.message);
                    transactionData = [];
                    renderCalendar(); 
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                transactionData = [];
                renderCalendar();
            });
        }
        
        // --- 5. Transaction Submission (Send to DB) ---
        transactionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'addTransaction');

            const amount = parseFloat(formData.get('amount'));
            if (isNaN(amount) || amount <= 0) {
                alert('Please enter a valid positive amount.');
                return;
            }
            if (formData.get('type') === 'expense' && !formData.get('category')) {
                alert('Please select an expense category.');
                return;
            }

            saveButton.disabled = true;
            saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
            
            fetch(BACKEND_FILE, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Transaction saved!');
                    
                    const modalInstance = bootstrap.Modal.getInstance(document.getElementById('transactionModal'));
                    modalInstance.hide();
                    this.reset();
                    
                    fetchMonthlyData(); 
                } else {
                    alert('Error saving transaction: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Submission Error:', error);
                alert('An error occurred while saving the transaction.');
            })
            .finally(() => {
                saveButton.disabled = false;
                saveButton.textContent = 'Save Transaction';
            });
        });

        // --- 6. Real-Time Clock Function ---
        function updateClock() {
            const now = new Date();
            // Format time as HH:MM:SS AM/PM (e.g., 09:07:26 PM)
            const timeString = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true 
            });
            document.getElementById('liveClock').textContent = timeString;
        }

        // --- Event Listeners for UI interaction ---
        
        // Type switching buttons
        switchExpenseBtn.addEventListener('click', () => {
            transactionTypeInput.value = 'expense';
            populateCategories('expense');
            updateModalHeader('expense');
        });

        switchIncomeBtn.addEventListener('click', () => {
            transactionTypeInput.value = 'income';
            populateCategories('income');
            updateModalHeader('income');
        });

        // Calendar Navigation
        document.getElementById('prevMonth').addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            fetchMonthlyData(); 
        });

        document.getElementById('nextMonth').addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            fetchMonthlyData(); 
        });
        
        // Default Modal setup on show
        document.getElementById('transactionModal').addEventListener('show.bs.modal', function (event) {
            // Default to expense setup
            transactionTypeInput.value = 'expense';
            populateCategories('expense');
            updateModalHeader('expense');
        });

        // Initial Load: Fetch data for the current month
        fetchMonthlyData();

        // Start the clock and set an interval for updates
        updateClock(); // Run immediately
        setInterval(updateClock, 1000); // Run every 1 second

    </script>
</body>
</html>