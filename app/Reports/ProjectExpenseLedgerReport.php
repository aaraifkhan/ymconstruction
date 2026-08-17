<?php

namespace App\Reports;

use App\Enums\JournalStatus;
use App\Models\Company;
use App\Models\JournalLine;
use App\Models\Project;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ProjectExpenseLedgerReport
{
    /**
     * @return array{
     *     project: Project,
     *     company: Company,
     *     from: ?string,
     *     to: ?string,
     *     total_project_cost: string,
     *     total_materials: string,
     *     total_labor_equipment: string,
     *     total_site_overheads: string,
     *     category_breakdown: array<string, array{label: string, group: string, count: int, total: string}>,
     *     rows: Collection<int, array<string, mixed>>
     * }
     */
    public function forProject(
        Company $company,
        Project $project,
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null
    ): array {
        $query = JournalLine::query()
            ->where('company_id', $company->getKey())
            ->where('project_id', $project->getKey())
            ->where('debit', '>', 0)
            ->whereHas('journalEntry', function ($q) use ($from, $to): void {
                $q->whereIn('status', [JournalStatus::Posted->value, JournalStatus::Reversed->value]);
                if ($from !== null) {
                    $q->whereDate('transaction_date', '>=', $from);
                }
                if ($to !== null) {
                    $q->whereDate('transaction_date', '<=', $to);
                }
            })
            ->with(['journalEntry.lines.account', 'party', 'account'])
            ->get()
            ->sortBy(fn (JournalLine $line): string => $line->journalEntry->transaction_date->format('Y-m-d').'-'.str_pad((string) $line->id, 8, '0', STR_PAD_LEFT))
            ->values();

        $totalCost = '0.0000';
        $totalMaterials = '0.0000';
        $totalLaborEquip = '0.0000';
        $totalSiteOverheads = '0.0000';
        $categoryBreakdown = [];
        $rows = collect();

        foreach ($query as $line) {
            $amount = (string) $line->debit;
            $totalCost = bcadd($totalCost, $amount, 4);

            $code = $line->account_code_snapshot ?: ($line->account?->code ?? '7000');
            $name = $line->account_name_snapshot ?: ($line->account?->name ?? 'Direct Cost');

            // Classify cost group
            $group = match (true) {
                in_array($code, ['7100', '7110', '7120', '7130', '7140', '7150', '7160', '7170', '7180'], true) => 'materials',
                in_array($code, ['7190', '7200', '7210', '7220', '7230'], true) => 'labor_equipment',
                default => 'site_overheads',
            };

            match ($group) {
                'materials' => $totalMaterials = bcadd($totalMaterials, $amount, 4),
                'labor_equipment' => $totalLaborEquip = bcadd($totalLaborEquip, $amount, 4),
                'site_overheads' => $totalSiteOverheads = bcadd($totalSiteOverheads, $amount, 4),
            };

            if (! isset($categoryBreakdown[$code])) {
                $categoryBreakdown[$code] = [
                    'label' => $name,
                    'group' => $group,
                    'count' => 0,
                    'total' => '0.0000',
                ];
            }
            $categoryBreakdown[$code]['count']++;
            $categoryBreakdown[$code]['total'] = bcadd($categoryBreakdown[$code]['total'], $amount, 4);

            // Paid from fund (contra line)
            $contraLine = $line->journalEntry->lines->firstWhere('id', '!=', $line->id);
            $paidFrom = $contraLine?->account?->name ?? $contraLine?->account_name_snapshot ?? 'Cash / Bank / Director';

            $rows->push([
                'id' => $line->id,
                'date' => $line->journalEntry->transaction_date->format('Y-m-d'),
                'voucher_number' => $line->journalEntry->voucher_number ?? $line->journalEntry->entry_number,
                'description' => $line->description ?: $line->journalEntry->description,
                'category' => $name,
                'category_code' => $code,
                'group' => $group,
                'paid_from' => $paidFrom,
                'party' => $line->party?->name,
                'amount' => $amount,
            ]);
        }

        return [
            'project' => $project,
            'company' => $company,
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
            'total_project_cost' => $totalCost,
            'total_materials' => $totalMaterials,
            'total_labor_equipment' => $totalLaborEquip,
            'total_site_overheads' => $totalSiteOverheads,
            'category_breakdown' => $categoryBreakdown,
            'rows' => $rows,
        ];
    }
}
