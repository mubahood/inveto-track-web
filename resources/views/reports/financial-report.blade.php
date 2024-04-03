<!DOCTYPE html>
<html>

<head>
    <title>Financial Report</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        .header {
            font-weight: bold;
        }
    </style>
</head>

<body>

    <h1>Financial Report</h1>

    <table class="header">
        <tr>
            <th>ID</th>
            <th>Created At</th>
            <th>Updated At</th>
            <th>Company</th>
            <th>User</th>
        </tr>
        <tr>
            <td>{{ $data->id }}</td>
            <td>{{ $data->created_at }}</td>
            <td>{{ $data->updated_at }}</td>
            <td>{{ $data->company->name ?? 'N/A' }}</td>
            <td>{{ $data->user->name ?? 'N/A' }}</td>
        </tr>
    </table>

    <br>

    <h2>Report Details</h2>

    <table class="details">
        <tr>
            <th>Type</th>
            <td>{{ $data->type }}</td>
        </tr>
        <tr>
            <th>Period</th>
            <td>{{ $data->period_type }}: {{ $data->start_date }} - {{ $data->end_date }}</td>
        </tr>
        <tr>
            <th>Currency</th>
            <td>{{ $data->currency }}</td>
        </tr>
        <tr>
            <th>Generated File</th>
            @if ($data->file_generated)
                <td>Yes ({{ $data->file }})</td>
            @else
                <td>No</td>
            @endif
        </tr>
    </table>

    <br>

    <h3>Financials</h3>

    <table class="financials">
        <tr>
            <th>Total Income</th>
            <td>{{ number_format($data->total_income, 2) }}</td>
        </tr>
        <tr>
            <th>Total Expense</th>
            <td>{{ number_format($data->total_expense, 2) }}</td>
        </tr>
        <tr>
            <th>Profit</th>
            <td>{{ number_format($data->profit, 2) }}</td>
        </tr>
    </table>

    @if ($data->include_finance_accounts)
        <h2>Finance Accounts</h2>
        <p>Details of included finance accounts are not displayed here for brevity.</p>
    @endif

    @if ($data->include_finance_records)
        <h2>Finance Records</h2>
        <p>Details of included finance records are not displayed here for brevity.</p>
    @endif

    @if ($data->inventory_include_categories)
        <h2>Inventory</h2>

        <h3>Categories</h3>
        <p>Details of included inventory categories are not displayed here for brevity.</p>

        @if ($data->inventory_include_sub_categories)
            <h4>Sub-Categories</h4>
            <p>Details of included inventory sub-categories are not displayed here for brevity.</p>
        @endif

        @if ($data->inventory_include_products)
            <h4>Products</h4>
            <p>Details of included inventory products are not displaying here for brevity.</p>
        @endif

        <table class="inventory">
            <tr>
                <th>Total Buying Price</th>
                <td>{{ number_format($data->inventory_total_buying_price, 2) }}</td>
            </tr>
            <tr>
                <th>Total Selling Price</th>
                <td>{{ number_format($data->inventory_total_selling_price, 2) }}</td>
            </tr>
            <tr>
                <th>Total Expected Profit</th>
                <td>{{ number_format($data->inventory_total_expected_profit, 2) }}</td>
            </tr>
            <tr>
                <th>Total Earned Profit</th>
                <td>{{ number_format($data->inventory_total_earned_profit, 2) }}</td>
            </tr>
        </table>
    @endif

    @if ($data->do_generate)
        <p>This report was automatically generated.</p>
    @endif

</body>

</html>
