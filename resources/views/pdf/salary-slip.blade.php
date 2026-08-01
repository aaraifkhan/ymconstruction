<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Salary Slip - {{ $entry->employee_name }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #14bf97;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #14bf97;
            margin: 0;
        }
        .title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #555;
            margin-top: 4px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .meta-table td {
            padding: 5px 8px;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            color: #666;
            width: 20%;
        }
        .val {
            width: 30%;
            color: #111;
        }
        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .breakdown-table th, .breakdown-table td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
        }
        .breakdown-table th {
            background-color: #f9fafb;
            font-weight: bold;
            text-align: left;
            font-size: 11px;
            color: #374151;
        }
        .text-right {
            text-align: right;
        }
        .text-bold {
            font-weight: bold;
        }
        .summary-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 4px;
            padding: 12px 16px;
            margin-bottom: 30px;
        }
        .net-pay {
            font-size: 16px;
            font-weight: bold;
            color: #15803d;
        }
        .footer-signatures {
            margin-top: 50px;
            width: 100%;
        }
        .signature-line {
            border-top: 1px solid #9ca3af;
            width: 180px;
            text-align: center;
            padding-top: 4px;
            font-size: 10px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="company-name">{{ $company->name }}</div>
                    <div class="title">Payslip for Period {{ $run->period_start->format('d M Y') }} - {{ $run->period_end->format('d M Y') }}</div>
                </td>
                <td class="text-right" style="vertical-align: top;">
                    <div style="font-size: 10px; color: #666;">Generated: {{ now()->format('d M Y') }}</div>
                    <div style="font-size: 11px; font-weight: bold; color: #333;">Run #{{ $run->id }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta-table">
        <tr>
            <td class="label">Employee ID:</td>
            <td class="val">{{ $entry->employee_code }}</td>
            <td class="label">Department:</td>
            <td class="val">{{ $entry->department ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Employee Name:</td>
            <td class="val">{{ $entry->employee_name }}</td>
            <td class="label">Designation:</td>
            <td class="val">{{ $entry->designation ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Period Days:</td>
            <td class="val">{{ $entry->period_days }}</td>
            <td class="label">Payable Days:</td>
            <td class="val">{{ $entry->payable_days }}</td>
        </tr>
        <tr>
            <td class="label">Payment Method:</td>
            <td class="val">{{ $entry->paymentMode() }}</td>
            <td class="label">Category:</td>
            <td class="val">{{ $entry->employment_category->label() }}</td>
        </tr>
    </table>

    <table class="breakdown-table">
        <thead>
            <tr>
                <th style="width: 50%;">Earnings</th>
                <th class="text-right" style="width: 50%;">Amount ({{ $entry->currency_code }})</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Basic Salary (Payable)</td>
                <td class="text-right">{{ number_format((float)$entry->payable_basic, 2) }}</td>
            </tr>
            @if((float)($entry->house_travel_allowance ?? 0) > 0)
            <tr>
                <td>House & Travel Allowance</td>
                <td class="text-right">{{ number_format((float)$entry->house_travel_allowance, 2) }}</td>
            </tr>
            @endif
            @if((float)($entry->fuel_allowance ?? 0) > 0)
            <tr>
                <td>Fuel Allowance</td>
                <td class="text-right">{{ number_format((float)$entry->fuel_allowance, 2) }}</td>
            </tr>
            @endif
            @if((float)($entry->mobile_allowance ?? 0) > 0)
            <tr>
                <td>Mobile Allowance</td>
                <td class="text-right">{{ number_format((float)$entry->mobile_allowance, 2) }}</td>
            </tr>
            @endif
            @if((float)($entry->internet_allowance ?? 0) > 0)
            <tr>
                <td>Internet Allowance</td>
                <td class="text-right">{{ number_format((float)$entry->internet_allowance, 2) }}</td>
            </tr>
            @endif
            @if((float)($entry->food_allowance ?? 0) > 0)
            <tr>
                <td>Food Allowance</td>
                <td class="text-right">{{ number_format((float)$entry->food_allowance, 2) }}</td>
            </tr>
            @endif
            @if((float)($entry->site_allowance ?? 0) > 0)
            <tr>
                <td>Site Allowance</td>
                <td class="text-right">{{ number_format((float)$entry->site_allowance, 2) }}</td>
            </tr>
            @endif
            @if((float)($entry->project_allowance ?? 0) > 0)
            <tr>
                <td>Project Allowance</td>
                <td class="text-right">{{ number_format((float)$entry->project_allowance, 2) }}</td>
            </tr>
            @endif
            @if((float)($entry->other_allowance ?? 0) > 0)
            <tr>
                <td>Other Allowance</td>
                <td class="text-right">{{ number_format((float)$entry->other_allowance, 2) }}</td>
            </tr>
            @endif
            @if((float)($entry->bonus_amount ?? 0) > 0)
            <tr>
                <td>Bonus</td>
                <td class="text-right">{{ number_format((float)$entry->bonus_amount, 2) }}</td>
            </tr>
            @endif
            @if((float)($entry->incentive_amount ?? 0) > 0)
            <tr>
                <td>Incentive</td>
                <td class="text-right">{{ number_format((float)$entry->incentive_amount, 2) }}</td>
            </tr>
            @endif
            <tr style="background-color: #f9fafb;" class="text-bold">
                <td>Total Gross Earnings</td>
                <td class="text-right">{{ number_format((float)$entry->gross_salary, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="breakdown-table">
        <thead>
            <tr>
                <th style="width: 50%;">Deductions</th>
                <th class="text-right" style="width: 50%;">Amount ({{ $entry->currency_code }})</th>
            </tr>
        </thead>
        <tbody>
            @if((float)($entry->absence_deduction ?? 0) > 0)
            <tr>
                <td>Absence Deduction</td>
                <td class="text-right">{{ number_format((float)$entry->absence_deduction, 2) }}</td>
            </tr>
            @endif
            @if((float)($entry->unpaid_leave_deduction ?? 0) > 0)
            <tr>
                <td>Unpaid Leave Deduction</td>
                <td class="text-right">{{ number_format((float)$entry->unpaid_leave_deduction, 2) }}</td>
            </tr>
            @endif
            @if((float)($entry->late_deduction ?? 0) > 0)
            <tr>
                <td>Late Deduction</td>
                <td class="text-right">{{ number_format((float)$entry->late_deduction, 2) }}</td>
            </tr>
            @endif
            @if((float)($entry->half_day_deduction ?? 0) > 0)
            <tr>
                <td>Half Day Deduction</td>
                <td class="text-right">{{ number_format((float)$entry->half_day_deduction, 2) }}</td>
            </tr>
            @endif
            @if((float)($entry->loan_advance_deduction ?? 0) > 0)
            <tr>
                <td>Loan & Advance Recovery</td>
                <td class="text-right">{{ number_format((float)$entry->loan_advance_deduction, 2) }}</td>
            </tr>
            @endif
            @if((float)($entry->other_deduction ?? 0) > 0)
            <tr>
                <td>Other Deductions</td>
                <td class="text-right">{{ number_format((float)$entry->other_deduction, 2) }}</td>
            </tr>
            @endif
            <tr style="background-color: #f9fafb;" class="text-bold">
                <td>Total Deductions</td>
                <td class="text-right">
                    {{ number_format(
                        (float)($entry->absence_deduction ?? 0) + (float)($entry->unpaid_leave_deduction ?? 0)
                        + (float)($entry->late_deduction ?? 0) + (float)($entry->half_day_deduction ?? 0)
                        + (float)($entry->loan_advance_deduction ?? 0) + (float)($entry->other_deduction ?? 0),
                        2
                    ) }}
                </td>
            </tr>
        </tbody>
    </table>

    <div class="summary-box">
        <table style="width: 100%;">
            <tr>
                <td class="net-pay">NET PAYABLE: {{ $entry->currency_code }} {{ number_format((float)$entry->net_salary, 2) }}</td>
                <td class="text-right" style="font-size: 11px; color: #374151;">
                    Bank: {{ number_format((float)$entry->bank_amount, 2) }} | Cash: {{ number_format((float)$entry->cash_amount, 2) }}
                </td>
            </tr>
        </table>
    </div>

    <table class="footer-signatures">
        <tr>
            <td style="width: 33%;">
                <div class="signature-line">Prepared By</div>
            </td>
            <td style="width: 34%; text-align: center;">
                <div class="signature-line" style="margin: 0 auto;">Approved By</div>
            </td>
            <td style="width: 33%; text-align: right;">
                <div class="signature-line" style="margin-left: auto;">Employee Signature</div>
            </td>
        </tr>
    </table>
</body>
</html>
