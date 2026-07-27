<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\CustomerInvoiceCategory;
use App\Enums\CustomerInvoiceStatus;
use Database\Factories\CustomerInvoiceLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'customer_invoice_id', 'company_id', 'item_id', 'unit_of_measure_id', 'revenue_account_id', 'cogs_account_id',
    'inventory_site_id', 'tax_code_id', 'original_customer_invoice_line_id', 'line_number',
    'item_code_snapshot', 'item_name_snapshot', 'uom_snapshot', 'description', 'quantity',
    'unit_rate', 'line_subtotal', 'tax_rate_snapshot', 'tax_method_snapshot', 'tax_amount',
    'line_total', 'cogs_unit_cost', 'cogs_amount',
])]
class CustomerInvoiceLine extends Model
{
    /** @use HasFactory<CustomerInvoiceLineFactory> */
    use HasFactory;

    protected $attributes = [
        'quantity' => 1, 'unit_rate' => 0, 'line_subtotal' => 0,
        'tax_rate_snapshot' => 0, 'tax_amount' => 0, 'line_total' => 0,
        'cogs_unit_cost' => 0, 'cogs_amount' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $line): void {
            $invoice = CustomerInvoice::query()->find($line->customer_invoice_id);
            if ($invoice === null || (int) $invoice->company_id !== (int) $line->company_id || ! $invoice->isEditable()) {
                throw ValidationException::withMessages(['customer_invoice_id' => 'Lines may only change on an editable same-company Customer Invoice.']);
            }
            if (bccomp((string) $line->quantity, '0', 4) !== 1 || bccomp((string) $line->unit_rate, '0', 4) === -1) {
                throw ValidationException::withMessages(['quantity' => 'Line quantity must be positive and rate cannot be negative.']);
            }
            $account = Account::query()->whereKey($line->revenue_account_id)
                ->where('company_id', $line->company_id)->where('account_type', AccountType::Revenue)
                ->where('is_active', true)->where('allows_manual_posting', true)->first();
            if ($account === null) {
                throw ValidationException::withMessages(['revenue_account_id' => 'Choose an active same-company Revenue posting account.']);
            }
            if ($line->item_id !== null) {
                $item = Item::query()->whereKey($line->item_id)->where('company_id', $line->company_id)->where('is_active', true)->first();
                if ($item === null || (int) $item->unit_of_measure_id !== (int) $line->unit_of_measure_id) {
                    throw ValidationException::withMessages(['item_id' => 'Choose an active same-company Item and its Unit of Measure.']);
                }
                $line->item_code_snapshot = $item->code;
                $line->item_name_snapshot = $item->name;
                $line->uom_snapshot = $item->unitOfMeasure()->value('code');
            } elseif (blank($line->item_name_snapshot)) {
                throw ValidationException::withMessages(['item_name_snapshot' => 'A line description is required.']);
            }
            if ($invoice->category === CustomerInvoiceCategory::TradingSale) {
                if ($line->item_id === null || ! $line->item?->track_inventory || $line->inventory_site_id === null) {
                    throw ValidationException::withMessages(['item_id' => 'Trading Sale lines require a stock-tracked Item and inventory Site.']);
                }
                if (! Account::query()->whereKey($line->cogs_account_id)->where('company_id', $line->company_id)
                    ->where('account_type', AccountType::Expense)->where('is_active', true)
                    ->where('allows_manual_posting', true)->exists()) {
                    throw ValidationException::withMessages(['cogs_account_id' => 'Trading Sale lines require an active same-company Cost of Goods Sold account.']);
                }
            } elseif ($line->inventory_site_id !== null) {
                throw ValidationException::withMessages(['inventory_site_id' => 'Only Trading Sale lines use an inventory Site.']);
            } elseif ($line->cogs_account_id !== null) {
                throw ValidationException::withMessages(['cogs_account_id' => 'Only Trading Sale lines use a Cost of Goods Sold account.']);
            }
            if ($line->inventory_site_id !== null && ! ProjectSite::query()->whereKey($line->inventory_site_id)
                ->where('company_id', $line->company_id)->exists()) {
                throw ValidationException::withMessages(['inventory_site_id' => 'Inventory Site must belong to the invoice company.']);
            }
            if ($line->tax_code_id !== null && ! TaxCode::query()->whereKey($line->tax_code_id)
                ->where('company_id', $line->company_id)->exists()) {
                throw ValidationException::withMessages(['tax_code_id' => 'Tax Code must belong to the invoice company.']);
            }
        });

        static::deleting(function (self $line): void {
            if (! $line->customerInvoice()->firstOrFail()->isEditable()) {
                throw ValidationException::withMessages(['customer_invoice_id' => 'Submitted Customer Invoice lines are immutable.']);
            }
        });
    }

    public function customerInvoice(): BelongsTo
    {
        return $this->belongsTo(CustomerInvoice::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function revenueAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'revenue_account_id');
    }

    public function cogsAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cogs_account_id');
    }

    public function inventorySite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class, 'inventory_site_id');
    }

    public function taxCode(): BelongsTo
    {
        return $this->belongsTo(TaxCode::class);
    }

    public function originalCustomerInvoiceLine(): BelongsTo
    {
        return $this->belongsTo(self::class, 'original_customer_invoice_line_id');
    }

    public function creditLines(): HasMany
    {
        return $this->hasMany(self::class, 'original_customer_invoice_line_id');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function postedCreditedQuantity(?int $excludingInvoiceId = null): string
    {
        $query = $this->creditLines()->whereHas('customerInvoice', fn ($query) => $query
            ->where('status', CustomerInvoiceStatus::Posted));
        if ($excludingInvoiceId !== null) {
            $query->where('customer_invoice_id', '!=', $excludingInvoiceId);
        }

        return (string) $query->sum('quantity');
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4', 'unit_rate' => 'decimal:4',
            'line_subtotal' => 'decimal:4', 'tax_rate_snapshot' => 'decimal:4',
            'tax_amount' => 'decimal:4', 'line_total' => 'decimal:4',
            'cogs_unit_cost' => 'decimal:4', 'cogs_amount' => 'decimal:4',
        ];
    }
}
