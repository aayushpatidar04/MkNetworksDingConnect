<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
 public function __construct(protected ?string $from = null, protected ?string $to = null, protected ?string $status = null) {}

 public function headings(): array
 {
 return [
 'Transaction ID', 'Receipt Number', 'Date', 'Retailer', 'Shop', 'Mobile Number',
 'Operator', 'Country', 'Amount', 'Ding Cost', 'Commission', 'Retailer Charged',
 'Ding Transaction ID', 'Status', 'Failure Reason',
 ];
 }

 public function query()
 {
 $query = Transaction::query()
 ->with(['user', 'operator', 'country'])
 ->orderByDesc('created_at');

 if ($this->from) {
 $query->whereDate('created_at', '>=', $this->from);
 }
 if ($this->to) {
 $query->whereDate('created_at', '<=', $this->to);
 }
 if ($this->status) {
 $query->where('status', $this->status);
 }

 return $query;
 }

 public function map($transaction): array
 {
 return [
 $transaction->id,
 $transaction->receipt_number ?? '-',
 $transaction->created_at->format('Y-m-d H:i:s'),
 $transaction->user->name ?? '-',
 $transaction->user->shop_name ?? '-',
 $transaction->mobile_number,
 $transaction->operator->name ?? '-',
 $transaction->country->name ?? '-',
 $transaction->amount,
 $transaction->ding_cost,
 $transaction->commission_amount,
 $transaction->retailer_charged,
 $transaction->ding_transaction_id ?? '-',
 ucfirst($transaction->status),
 $transaction->failure_reason ?? '-',
 ];
 }

 public function styles(Worksheet $sheet)
 {
 $sheet->getStyle('1:1')->getFont()->setBold(true);
 $sheet->getStyle('1:1')->getFill()
 ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
 ->getStartColor()->setARGB('FFE5E7EB');
 }
}
