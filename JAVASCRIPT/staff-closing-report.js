document.addEventListener('DOMContentLoaded', () => {

    // --- Total Cash Calculation ---
    const cashSale = document.getElementById('cash_sale');
    const cashFloat = document.getElementById('cash_float');
    const qrSale = document.getElementById('qr_sale');
    const totalCash = document.getElementById('total_cash');
    

    [cashSale, cashFloat].forEach(el => el.addEventListener('input', () => {
        const total = parseFloat(cashSale.value || 0) + parseFloat(cashFloat.value || 0);
        totalCash.value = total.toFixed(2);
        updateExpectedCash();
    }));

    // --- Payment Total & Add/Delete Row ---
    const paymentTableBody = document.getElementById('paymentTable').querySelector('tbody');
    const totalPaymentInput = document.getElementById('total_payment');
    const addPaymentRowBtn = document.getElementById('addPaymentRow');
    const deletePaymentRowBtn = document.getElementById('deletePaymentRow');

    function updatePaymentTotal() {
        let sum = 0;
        paymentTableBody.querySelectorAll('input[name="payment_amount[]"]').forEach(inp => {
            sum += parseFloat(inp.value || 0);
        });
        totalPaymentInput.value = sum.toFixed(2);
        updateExpectedCash();
    }

    // Recalculate total when input changes
    paymentTableBody.addEventListener('input', updatePaymentTotal);

    // Add new payment row
    addPaymentRowBtn.addEventListener('click', () => {
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>
                <input type="text" name="payment_type[]" class="form-control payment-type" placeholder="Payment Type">
                <div class="invalid-feedback">Payment type is required</div>
            </td>
            <td>
                <input type="number" name="payment_amount[]" class="form-control payment-amount" step="0.01" min="0.01">
                <div class="invalid-feedback">Payment amount must be greater than 0</div>
            </td>
        `;
        paymentTableBody.appendChild(newRow);
    });

    // Delete last payment row
    deletePaymentRowBtn.addEventListener('click', () => {
        const rows = paymentTableBody.querySelectorAll('tr');
        if (rows.length > 0) {
            rows[rows.length - 1].remove();
            updatePaymentTotal();
        }
    });

    // --- Cash Breakdown Calculation ---
    const cashTable = document.getElementById('cashTable');
    const grandTotalInput = document.getElementById('grandTotal');
    const actualCashInput = document.getElementById('actual_cash');
    const expectedCashInput = document.getElementById('expected_cash');
    const differenceInput = document.getElementById('difference');

    // Calculate row total and grand total
    cashTable.addEventListener('input', () => {
        let grandTotal = 0;
        
        cashTable.querySelectorAll('tbody tr').forEach(row => {
            const qtyInput = row.querySelector('.qty-input');
            const rowTotalInput = row.querySelector('.row-total');
            const denomination = parseFloat(row.querySelector('input[name="denomination[]"]').value);
            const qty = parseFloat(qtyInput.value || 0);
            const rowTotal = denomination * qty;
            
            rowTotalInput.value = rowTotal.toFixed(2);
            grandTotal += rowTotal;
        });
        
        grandTotalInput.value = grandTotal.toFixed(2);
        actualCashInput.value = grandTotal.toFixed(2);
        updateExpectedCash();
    });

    // --- Expected Cash & Difference Calculation ---
    function updateExpectedCash() {
        const totalCashValue = parseFloat(totalCash.value || 0);
        const totalPaymentValue = parseFloat(totalPaymentInput.value || 0);
        const qrSaleValue = parseFloat(qrSale.value || 0);
        
        const expected = totalCashValue - totalPaymentValue;
        expectedCashInput.value = expected.toFixed(2);
        
        const actual = parseFloat(actualCashInput.value || 0);
        
        let difference;
if (expected < 0) {
    difference = actual + expected;
} else {
    difference = actual - expected;
}
differenceInput.value = difference.toFixed(2);
        
        // Color coding for difference
        if (difference < 0) {
            differenceInput.classList.add('text-danger');
            differenceInput.classList.remove('text-success');
        } else if (difference > 0) {
            differenceInput.classList.add('text-success');
            differenceInput.classList.remove('text-danger');
        } else {
            differenceInput.classList.remove('text-danger', 'text-success');
        }
    }

    // Initialize calculations
    updatePaymentTotal();
    updateExpectedCash();
    
    
});