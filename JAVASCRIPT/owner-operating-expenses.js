/**
 * Owner Operating Expenses JavaScript
 * Handles dynamic form functionality for operating expenses
 */

document.addEventListener('DOMContentLoaded', function() {
    // Global variables
    let currentOutletId = null;
    let staffList = [];

    // Initialize the interface
    initializeOperatingExpenses();

    // Event Listeners
    document.getElementById('outletSelect').addEventListener('change', handleOutletChange);
    
    // Add row buttons
    document.getElementById('addSalaryRow').addEventListener('click', () => addExpenseRow('salary'));
    document.getElementById('addRentalRow').addEventListener('click', () => addExpenseRow('rental'));
    document.getElementById('addUtilitiesRow').addEventListener('click', () => addExpenseRow('utilities'));
    document.getElementById('addAdvertisementRow').addEventListener('click', () => addExpenseRow('advertisement'));
    document.getElementById('addOthersRow').addEventListener('click', () => addExpenseRow('others'));
    
    // Delete row buttons
    document.getElementById('deleteSalaryRow').addEventListener('click', () => deleteExpenseRow('salary'));
    document.getElementById('deleteRentalRow').addEventListener('click', () => deleteExpenseRow('rental'));
    document.getElementById('deleteUtilitiesRow').addEventListener('click', () => deleteExpenseRow('utilities'));
    document.getElementById('deleteAdvertisementRow').addEventListener('click', () => deleteExpenseRow('advertisement'));
    document.getElementById('deleteOthersRow').addEventListener('click', () => deleteExpenseRow('others'));

    /**
     * Initialize the operating expenses interface
     */
    function initializeOperatingExpenses() {
        // Set maximum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('record_date').setAttribute('max', today);
        
        // Initialize all expense sections with no rows
        const expenseTypes = ['salary', 'rental', 'utilities', 'advertisement', 'others'];
        expenseTypes.forEach(type => {
            clearExpenseTable(type);
        });
        
        // Update totals
        updateAllTotals();
    }

    /**
     * Handle outlet selection change
     */
    function handleOutletChange() {
        const outletSelect = document.getElementById('outletSelect');
        currentOutletId = outletSelect.value;
        
        // Clear staff list and salary rows when outlet changes
        staffList = [];
        clearExpenseTable('salary');
        updateTotal('salary', 0);
        
        if (currentOutletId) {
            // Fetch staff for selected outlet
            fetchStaffForOutlet(currentOutletId);
        }
        
        // Update all totals
        updateAllTotals();
    }

    /**
     * Fetch staff members for the selected outlet
     * @param {number} outletId - The outlet ID
     */
    function fetchStaffForOutlet(outletId) {
        // AJAX call to get staff data
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'get_staff_by_outlet.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        staffList = response.data;
                        console.log('Staff data loaded:', staffList);
                    } else {
                        console.error('Error fetching staff:', response.message);
                        staffList = [];
                    }
                } catch (e) {
                    console.error('Error parsing staff data:', e);
                    staffList = [];
                }
            } else {
                console.error('AJAX error:', xhr.status);
                staffList = [];
            }
        };
        
        xhr.onerror = function() {
            console.error('Network error fetching staff data');
            staffList = [];
        };
        
        xhr.send('outlet_id=' + encodeURIComponent(outletId));
    }

    /**
     * Add a new row to the specified expense table
     * @param {string} type - The expense type
     */
    function addExpenseRow(type) {
        const table = document.getElementById(`${type}Table`).getElementsByTagName('tbody')[0];
        const newRow = table.insertRow();
        newRow.className = 'new-row';
        
        if (type === 'salary') {
            addSalaryRow(newRow);
        } else {
            addGenericExpenseRow(newRow, type);
        }
        
        // Add input event listeners for real-time calculation
        addInputEventListeners(newRow, type);
        
        // Update totals
        updateAllTotals();
        
        // Remove highlight animation after it completes
        setTimeout(() => {
            newRow.classList.remove('new-row');
        }, 1500);
    }

    /**
     * Add a salary-specific row
     * @param {HTMLTableRowElement} row - The table row element
     */
    function addSalaryRow(row) {
        // Staff dropdown cell
        const staffCell = row.insertCell(0);
        const staffSelect = document.createElement('select');
        staffSelect.name = 'salary_staff_id[]';
        staffSelect.className = 'form-control';
        staffSelect.required = true;
        
        // Add default option
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = currentOutletId ? '-- Select Staff --' : '-- Please choose outlet first --';
        defaultOption.disabled = true;
        defaultOption.selected = true;
        staffSelect.appendChild(defaultOption);
        
        // Add staff options if available
        if (staffList.length > 0 && currentOutletId) {
            staffList.forEach(staff => {
                const option = document.createElement('option');
                option.value = staff.staff_id;
                option.textContent = staff.staff_name;
                staffSelect.appendChild(option);
            });
        } else if (currentOutletId) {
            const noStaffOption = document.createElement('option');
            noStaffOption.value = '';
            noStaffOption.textContent = '-- No staff available --';
            noStaffOption.disabled = true;
            staffSelect.appendChild(noStaffOption);
        }
        
        staffCell.appendChild(staffSelect);
        
        // Amount cell
        const amountCell = row.insertCell(1);
        const amountInput = document.createElement('input');
        amountInput.type = 'number';
        amountInput.name = 'salary_amount[]';
        amountInput.className = 'form-control amount-input';
        amountInput.placeholder = '0.00';
        amountInput.step = '0.01';
        amountInput.min = '0';
        amountInput.required = true;
        amountCell.appendChild(amountInput);
    }

    /**
     * Add a generic expense row (for rental, utilities, advertisement, others)
     * @param {HTMLTableRowElement} row - The table row element
     * @param {string} type - The expense type
     */
    function addGenericExpenseRow(row, type) {
        // Expense name cell
        const nameCell = row.insertCell(0);
        const nameInput = document.createElement('input');
        nameInput.type = 'text';
        nameInput.name = `${type}_name[]`;
        nameInput.className = 'form-control';
        nameInput.placeholder = `Enter ${type} expense name`;
        nameInput.required = true;
        nameCell.appendChild(nameInput);
        
        // Amount cell
        const amountCell = row.insertCell(1);
        const amountInput = document.createElement('input');
        amountInput.type = 'number';
        amountInput.name = `${type}_amount[]`;
        amountInput.className = 'form-control amount-input';
        amountInput.placeholder = '0.00';
        amountInput.step = '0.01';
        amountInput.min = '0';
        amountInput.required = true;
        amountCell.appendChild(amountInput);
    }

    /**
     * Add input event listeners to a row for real-time calculation
     * @param {HTMLTableRowElement} row - The table row element
     * @param {string} type - The expense type
     */
    function addInputEventListeners(row, type) {
        const amountInput = row.querySelector('.amount-input');
        if (amountInput) {
            amountInput.addEventListener('input', () => {
                updateSectionTotal(type);
                updateTotalOperatingExpense();
            });
        }
        
        // For generic expense rows, also listen to name input
        if (type !== 'salary') {
            const nameInput = row.querySelector('input[type="text"]');
            if (nameInput) {
                nameInput.addEventListener('input', () => {
                    // Clear validation when user starts typing
                    nameInput.classList.remove('is-invalid');
                });
            }
        }
        
        // For salary, also update when staff selection changes
        if (type === 'salary') {
            const staffSelect = row.querySelector('select');
            if (staffSelect) {
                staffSelect.addEventListener('change', () => {
                    // Clear validation when user makes selection
                    staffSelect.classList.remove('is-invalid');
                    
                    // Validate that amount is provided when staff is selected
                    const amountInput = row.querySelector('.amount-input');
                    if (staffSelect.value && (!amountInput.value || parseFloat(amountInput.value) <= 0)) {
                        amountInput.focus();
                    }
                });
            }
        }
        
        // Clear validation when user types in amount
        if (amountInput) {
            amountInput.addEventListener('input', () => {
                amountInput.classList.remove('is-invalid');
            });
        }
    }

    /**
     * Delete the last row from the specified expense table
     * @param {string} type - The expense type
     */
    function deleteExpenseRow(type) {
        const table = document.getElementById(`${type}Table`).getElementsByTagName('tbody')[0];
        if (table.rows.length > 0) {
            table.deleteRow(-1); // Delete last row
            updateSectionTotal(type);
            updateTotalOperatingExpense();
        }
    }

    /**
     * Clear all rows from an expense table
     * @param {string} type - The expense type
     */
    function clearExpenseTable(type) {
        const table = document.getElementById(`${type}Table`).getElementsByTagName('tbody')[0];
        table.innerHTML = '';
    }

    /**
     * Update the total for a specific expense section
     * @param {string} type - The expense type
     */
    function updateSectionTotal(type) {
        const table = document.getElementById(`${type}Table`);
        const amountInputs = table.querySelectorAll('.amount-input');
        let total = 0;
        
        amountInputs.forEach(input => {
            const value = parseFloat(input.value) || 0;
            total += value;
        });
        
        updateTotal(type, total);
    }

    /**
     * Update the total display for a specific expense type
     * @param {string} type - The expense type
     * @param {number} total - The total amount
     */
    function updateTotal(type, total) {
        const totalElement = document.getElementById(`total_${type}`);
        if (totalElement) {
            totalElement.value = total.toFixed(2);
        }
    }

    /**
     * Update all section totals
     */
    function updateAllTotals() {
        const expenseTypes = ['salary', 'rental', 'utilities', 'advertisement', 'others'];
        expenseTypes.forEach(type => {
            updateSectionTotal(type);
        });
        updateTotalOperatingExpense();
    }

    /**
     * Update the total operating expense display
     */
    function updateTotalOperatingExpense() {
        let grandTotal = 0;
        const expenseTypes = ['salary', 'rental', 'utilities', 'advertisement', 'others'];
        
        expenseTypes.forEach(type => {
            const totalElement = document.getElementById(`total_${type}`);
            if (totalElement) {
                const value = parseFloat(totalElement.value) || 0;
                grandTotal += value;
            }
        });
        
        const totalOperatingExpense = document.getElementById('total_operating_expense');
        if (totalOperatingExpense) {
            totalOperatingExpense.value = grandTotal.toFixed(2);
        }
    }
});