<?php

namespace App\Http\Controllers\api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookStoreRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::all();
        return ResponseHelper::jsonResponse(true, 'Daftar buku berhasil diambil', BookResource::collection($books), 200);
    }

    public function show($id)
    {
        $book = Book::find($id);
        if (!$book) {
            return ResponseHelper::jsonResponse(false, 'Buku tidak ditemukan', null, 404);
        }
        return ResponseHelper::jsonResponse(true, 'Detail buku berhasil diambil', BookResource::make($book), 200);
    }

    public function store(BookStoreRequest $request)
    {
        $validated = $request->validated();

        $book = new Book();
        $book->title = $validated['title'];
        $book->slug = Str::slug($validated['title']);
        $book->cover = $request->file('cover')->store('covers_books', 'public');
        $book->category_id = $validated['category_id'];
        $book->author = $validated['author'];
        $book->publisher = $validated['publisher'];
        $book->isbn = $validated['isbn'];
        $book->number_book = $validated['number_book'];
        $book->publication_year = $validated['publication_year'];
        $book->stock = $validated['stock'];
        $book->save();

        return ResponseHelper::jsonResponse(true, 'Buku berhasil ditambahkan', BookResource::make($book), 201);
    }

    public function update(Request $request, $id)
    {
        $book = Book::find($id);
        if (!$book) {
            return ResponseHelper::jsonResponse(false, 'Buku tidak ditemukan', null, 404);
        }

        $book->title = $request->input('title', $book->title);
        $book->slug = $request->input('slug', Str::slug($book->title));
        $book->author = $request->input('author', $book->author);
        $book->publisher = $request->input('publisher', $book->publisher);
        $book->number_book = $request->input('number_book', $book->number_book);
        $book->publication_year = $request->input('publication_year', $book->publication_year);
        $book->category_id = $request->input('category_id', $book->category_id);
        $book->stock = $request->input('stock', $book->stock);

        if ($request->hasFile('cover')) {
            if ($book->cover) {
                Storage::disk('public')->delete($book->cover);
            }
            $book->cover = $request->file('cover')->store('covers_books', 'public');
        }

        $book->save();

        return ResponseHelper::jsonResponse(true, 'Buku berhasil diupdate', BookResource::make($book), 200);
    }

    public function destroy($id)
    {
        $book = Book::find($id);
        if (!$book) {
            return ResponseHelper::jsonResponse(false, 'Buku tidak ditemukan', null, 404);
        }

        if ($book->cover) {
            Storage::disk('public')->delete($book->cover);
        }

        $book->delete();

        return ResponseHelper::jsonResponse(true, 'Buku berhasil dihapus', null, 200);
    }
}
