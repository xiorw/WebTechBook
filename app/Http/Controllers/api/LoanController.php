<?php

namespace App\Http\Controllers\api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoanStoreRequest;
use App\Http\Resources\LoanResource;
use App\Models\Book;
use App\Models\Loan;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    /**
     * Menampilkan semua data peminjaman
     */
    public function index()
    {
        $loans = Loan::all();
        return ResponseHelper::jsonResponse(true, 'Buku berhasil di fetch', LoanResource::collection($loans), 200);
    }

    /**
     * Menyimpan data peminjaman baru
     */
    public function store(LoanStoreRequest $request)
    {
        $book = Book::find($request->book_id);

        if (!$book) {
            return ResponseHelper::jsonResponse(false, 'Buku tidak ditemukan', null, 404);
        }

        if ($book->stock < 1) {
            return ResponseHelper::jsonResponse(false, 'Stock buku ini habis', null, 400);
        }

        $existingLoan = Loan::where('user_id', $request->user_id)
            ->where('book_id', $request->book_id)
            ->where('status', 'borrowed')
            ->first();

        if ($existingLoan) {
            return ResponseHelper::jsonResponse(false, 'Kamu sudah meminjam buku ini', null, 400);
        }

        $loanDate = Carbon::now();
        $dueDate = $loanDate->copy()->addDays(7);

        $loan = new Loan();
        $loan->user_id = $request->user_id;
        $loan->book_id = $request->book_id;
        $loan->loan_date = $loanDate;
        $loan->due_date = $dueDate;
        $loan->status = 'borrowed';
        $loan->save();

        $book->decrement('stock');

        return ResponseHelper::jsonResponse(true, 'Buku berhasil disewa', LoanResource::make($loan), 201);
    }

    /**
     * Mengembalikan buku yang dipinjam
     */
    public function returnLoan($loan_id)
    {
        $loan = Loan::find($loan_id);

        if (!$loan) {
            return ResponseHelper::jsonResponse(false, 'Data peminjaman tidak ditemukan', null, 404);
        }

        if ($loan->status === 'returned') {
            return ResponseHelper::jsonResponse(false, 'Buku ini sudah dikembalikan', null, 400);
        }

        $loan->status = 'returned';
        $loan->save();

        $book = Book::find($loan->book_id);
        if ($book) {
            $book->increment('stock');
        }

        return ResponseHelper::jsonResponse(true, 'Buku berhasil dikembalikan', LoanResource::make($loan), 200);
    }
}
