<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Display a listing of transactions with filtering
     */
    public function index(Request $request)
    {
        $selectedMonth = $request->get('month', '');
        $selectedFiscalYear = $request->get('fiscal_year', '');
        $selectedCategory = $request->get('category', '');

        // Get all parent transactions
        $query = Transaction::whereNull('parent_id')->with('children');

        // Apply filters
        if ($selectedMonth !== '') {
            $query->whereMonth('transaction_date', $selectedMonth);
        } elseif ($selectedFiscalYear !== 'all' && $selectedFiscalYear !== '') {
            $query->whereBetween('transaction_date', [
                date('Y-m-d', strtotime("$selectedFiscalYear-04-01")),
                date('Y-m-d', strtotime(($selectedFiscalYear + 1) . "-03-31"))
            ]);
        }

        if ($selectedCategory !== '') {
            $query->where('category', $selectedCategory);
        }

        $displayRows = $query->orderBy('transaction_date', 'asc')->get();

        // Get all transactions for filter options
        $allTransactions = Transaction::whereNull('parent_id')->orderBy('transaction_date', 'asc')->get();

        // Build available months, fiscal years, and categories
        $availableMonths = [];
        $availableFiscalYears = [];
        $availableCategories = [];

        foreach ($allTransactions as $transaction) {
            $date = $transaction->transaction_date;
            $month = $date->format('m');
            $year = (int)$date->format('Y');
            $monthNum = (int)$date->format('n');
            $fiscalYear = $monthNum >= 4 ? $year : $year - 1;

            if (!isset($availableMonths[$month])) {
                $availableMonths[$month] = [
                    'key' => $month,
                    'label' => $date->format('n月')
                ];
            }

            if (!isset($availableFiscalYears[$fiscalYear])) {
                $availableFiscalYears[$fiscalYear] = [
                    'key' => (string)$fiscalYear,
                    'label' => sprintf('%d年度', $fiscalYear)
                ];
            }

            if (!isset($availableCategories[$transaction->category])) {
                $availableCategories[$transaction->category] = $transaction->category;
            }
        }

        // Set current fiscal year if not selected
        $currentFiscalYear = (int)(date('n') >= 4 ? date('Y') : date('Y') - 1);
        if (!isset($availableFiscalYears[$currentFiscalYear])) {
            $availableFiscalYears[$currentFiscalYear] = [
                'key' => (string)$currentFiscalYear,
                'label' => sprintf('%d年度', $currentFiscalYear)
            ];
        }

        ksort($availableFiscalYears);
        $availableFiscalYears = array_reverse($availableFiscalYears, true);

        if ($selectedCategory !== '' && !isset($availableCategories[$selectedCategory])) {
            $selectedCategory = '';
        }

        if ($selectedFiscalYear === '') {
            $selectedFiscalYear = (string)$currentFiscalYear;
        } elseif ($selectedFiscalYear !== 'all' && !isset($availableFiscalYears[$selectedFiscalYear])) {
            $selectedFiscalYear = (string)$currentFiscalYear;
        }

        if ($selectedMonth !== '' && !isset($availableMonths[$selectedMonth])) {
            $selectedMonth = '';
        }

        ksort($availableMonths);

        // Calculate running balance
        $current_balance = 0;
        foreach ($displayRows as $row) {
            $income = $row->income ?? 0;
            $expense = $row->expense ?? 0;
            $current_balance += ($income - $expense);
            $row->calculated_balance = $current_balance;
        }

        // Group by month
        $groupedTransactions = [];
        foreach ($displayRows as $row) {
            $monthKey = $row->transaction_date->format('Y-m');
            if (!isset($groupedTransactions[$monthKey])) {
                $groupedTransactions[$monthKey] = [
                    'label' => $row->transaction_date->format('Y年n月'),
                    'rows' => [],
                    'total_income' => 0,
                    'total_expense' => 0,
                    'last_balance' => 0
                ];
            }
            $groupedTransactions[$monthKey]['rows'][] = $row;
            $groupedTransactions[$monthKey]['total_income'] += $row->income ?? 0;
            $groupedTransactions[$monthKey]['total_expense'] += $row->expense ?? 0;
            $groupedTransactions[$monthKey]['last_balance'] = $row->calculated_balance;
        }

        return view('transactions.index', [
            'availableMonths' => array_values($availableMonths),
            'availableFiscalYears' => array_values(array_reverse($availableFiscalYears, true)),
            'availableCategories' => array_values($availableCategories),
            'selectedMonth' => $selectedMonth,
            'selectedFiscalYear' => $selectedFiscalYear,
            'selectedCategory' => $selectedCategory,
            'groupedTransactions' => $groupedTransactions
        ]);
    }

    /**
     * Show the form for creating a new transaction
     */
    public function create()
    {
        $today = now()->format('Y-m-d');
        $type = 'expense';
        $parents = [[
            'date' => $today,
            'description' => '',
            'category' => '',
            'amount' => '',
            'image' => '',
            'image_path' => '',
            'children' => []
        ]];

        return view('transactions.create', compact('today', 'type', 'parents'));
    }

    /**
     * Confirm transaction data before saving
     */
    public function confirm(Request $request)
    {
        $today = now()->format('Y-m-d');
        $type = $request->input('type', 'expense');
        $parents = [];
        $error = '';

        if ($request->hasFile('parent_image')) {
            $images = $request->file('parent_image');
            $dates = $request->input('parent_date', []);
            $descriptions = $request->input('parent_description', []);
            $categories = $request->input('parent_category', []);
            $amounts = $request->input('parent_amount', []);

            foreach ($dates as $parentIdx => $date) {
                $date = trim($date);
                $description = trim($descriptions[$parentIdx] ?? '');
                $category = trim($categories[$parentIdx] ?? '');
                $amount = $amounts[$parentIdx] ?? '';

                $uploadedImagePath = '';
                $uploadedImageName = '';

                if (isset($images[$parentIdx]) && $images[$parentIdx]->isValid()) {
                    $uploadedImageName = $images[$parentIdx]->getClientOriginalName();
                    $uploadedImagePath = $this->saveUploadedImage($images[$parentIdx]);
                }

                if ($date !== '' && $description !== '' && $category !== '' && $amount !== '' && is_numeric($amount) && (int)$amount > 0) {
                    $parentData = [
                        'date' => $date,
                        'description' => $description,
                        'category' => $category,
                        'amount' => (int)$amount,
                        'image' => $uploadedImageName,
                        'image_path' => $uploadedImagePath,
                        'children' => []
                    ];

                    if ($request->has("child_name.$parentIdx")) {
                        $childNames = $request->input("child_name.$parentIdx", []);
                        $childQtys = $request->input("child_qty.$parentIdx", []);
                        $childPrices = $request->input("child_price.$parentIdx", []);

                        foreach ($childNames as $childIdx => $itemName) {
                            $itemName = trim($itemName);
                            if ($itemName !== '') {
                                $qty = isset($childQtys[$childIdx]) ? (int)$childQtys[$childIdx] : 1;
                                $price = isset($childPrices[$childIdx]) ? (int)$childPrices[$childIdx] : 0;

                                $parentData['children'][] = [
                                    'name' => $itemName,
                                    'quantity' => $qty,
                                    'unit_price' => $price
                                ];
                            }
                        }
                    }

                    $parents[] = $parentData;
                }
            }
        }

        if (empty($parents)) {
            $error = 'データが入力されていません。';
            $type = $request->input('type', 'expense');
            return view('transactions.create', compact('today', 'type', 'parents', 'error'));
        }

        return view('transactions.confirm', compact('today', 'type', 'parents'));
    }

    /**
     * Store a newly created transaction in storage
     */
    public function store(Request $request)
    {
        $type = $request->input('type', 'expense');
        $parents = json_decode($request->input('parents_data', '[]'), true);

        if (empty($parents)) {
            return redirect()->route('transactions.index')->with('error', 'データが入力されていません。');
        }

        try {
            DB::transaction(function () use ($type, $parents) {
                foreach ($parents as $parentData) {
                    $income = ($type === 'income') ? $parentData['amount'] : 0;
                    $expense = ($type === 'expense') ? $parentData['amount'] : 0;

                    $imagePath = $parentData['image_path'] ?? '';

                    $parentTransaction = Transaction::create([
                        'transaction_date' => $parentData['date'],
                        'category' => $parentData['category'],
                        'description' => $parentData['description'],
                        'income' => $income,
                        'expense' => $expense,
                        'image_path' => $imagePath
                    ]);

                    if (!empty($parentData['children'])) {
                        foreach ($parentData['children'] as $child) {
                            Transaction::create([
                                'parent_id' => $parentTransaction->id,
                                'transaction_date' => $parentData['date'],
                                'item_name' => $child['name'],
                                'quantity' => $child['quantity'],
                                'unit_price' => $child['unit_price'],
                                'category' => $parentData['category'],
                                'description' => $parentData['description']
                            ]);
                        }
                    }
                }
            });

            return redirect()->route('transactions.index')->with('success', 'トランザクションが登録されました。');
        } catch (\Exception $e) {
            return redirect()->route('transactions.index')->with('error', 'エラー: ' . $e->getMessage());
        }
    }

    /**
     * Delete a transaction
     */
    public function destroy($id)
    {
        try {
            $transaction = Transaction::findOrFail($id);
            $transaction->delete();
            return redirect()->back()->with('success', 'トランザクションが削除されました。');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'エラー: ' . $e->getMessage());
        }
    }

    /**
     * Save uploaded image
     */
    private function saveUploadedImage($file)
    {
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        $ext = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, $allowedExts)) {
            return '';
        }

        $uniqueName = 'receipt_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $path = $file->storeAs('uploads', $uniqueName, 'public');

        return $path ? 'uploads/' . $uniqueName : '';
    }
}
