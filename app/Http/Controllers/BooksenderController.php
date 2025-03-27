<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Book_type;
use App\Models\Book_type_parent;
use App\Models\Permission;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Webklex\PHPIMAP\ClientManager;

class BooksenderController extends Controller
{
    public $users;
    public $permission;
    public $permission_data;
    public $permission_id;
    public $position_id;
    public $position_name;
    public $signature;
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->users = Auth::user();
            $sql = Permission::where('id', $this->users->permission_id)->first();
            $this->permission = explode(',', $sql->can_status);
            $this->permission_id = $this->users->permission_id;
            $this->permission_data = $sql;
            $sql = Position::where('id', $this->users->position_id)->first();
            $this->position_id = $this->users->position_id;
            $this->position_name = ($sql != null) ? $sql->position_name : '';
            $this->signature = url("/storage/users/" . auth()->user()->signature);
            return $next($request);
        });
    }

    public function index()
    {
        // if ($this->permission_id != 1 && $this->permission_id != 9) {
        //     return redirect('/book/show');
        // }
        $data['permission_data'] = $this->permission_data;
        $data['function_key'] = 'bookSender';
        $data['book_type'] = Book_type::where('type', 2)->get();
        $data['book_type_parent'] = Book_type_parent::get();
        $books = Book::where('selectBookregist', $data['book_type'][0]->id)->where('position_id', auth()->user()->position_id)->where('selectBookregistSup', $data['book_type_parent'][0]->id)->orderBy('inputBookregistNumber', 'desc')->first();
        if ($books) {
            $data['inputBookregistNumber'] = $books->inputBookregistNumber + 1;
        } else {
            $data['inputBookregistNumber'] = 1;
        }
        $data['position'] = Position::get();
        $data['users'] = User::select('users.*', 'permissions.permission_name')->leftJoin('permissions', 'users.permission_id', '=', 'permissions.id')->get();
        return view('booksender.index', $data);
    }

    public function bookType(Request $request)
    {
        $id = $request->input('id');
        $parent_id = $request->input('parent');
        if ($id == 3) {
            $books = Book::where('selectBookregist', $id)->where('position_id', auth()->user()->position_id)->where('selectBookregistSup', $parent_id)->orderBy('inputBookregistNumber', 'desc')->first();
        } else {
            $books = Book::where('selectBookregist', $id)->where('selectBookregistSup', $parent_id)->orderBy('inputBookregistNumber', 'desc')->first();
        }
        if ($books) {
            $inputBookregistNumber = $books->inputBookregistNumber + 1;
        } else {
            $inputBookregistNumber = 1;
        }
        return response()->json($inputBookregistNumber);
    }

    public function getPosition(Request $request)
    {
        $id = $request->input('id');
        $position = Position::find($id);
        return response()->json($position);
    }

    public function save(Request $request)
    {
        $book = new Book;
        $book->selectBookregist = $request['selectBookregist'];
        $book->selectBookregistSup = $request['selectBookregist_parent'];
        $book->inputBookregistNumber = $request['inputBookregistNumber'];
        $book->inputBooknumberOrgStruc = $request['inputBooknumberOrgStruc'];
        $book->selectBookcircular = $request['selectBookcircular'];
        $book->selectLevelSpeed = $request['selectLevelSpeed'];
        $book->inputDated = $request['inputDated'];
        $book->inputSubject = $request['inputSubject'];
        $book->inputBookto = $request['inputBookto'];
        $book->inputBookref = $request['inputBookref'];
        $book->inputAttachments = $request['inputAttachments'];
        $book->inputContent = $request['inputContent'];
        $book->selectUsersParent = $request['selectUsersParent'];
        $book->selectPositionParent = $request['selectPositionParent'];
        $book->created_by = $this->users->id;
        $book->updated_by = $this->users->id;
        $book->status = 1;
        $book->type = 2;
        if ($request->hasFile('file-input')) {
            $file = $request->file('file-input');
            $filePath = $file->store('uploads');
            $book->file = ($filePath) ? $filePath : '';
        }
        if ($request->hasFile('file-attachments')) {
            $attachments = $request->file('file-attachments');
            $filePathAttachments = $attachments->store('attachments');
            $book->fileAttachments = ($filePathAttachments) ? $filePathAttachments : '';
        }
        if ($book->save()) {
            log_active([
                'users_id' => auth()->user()->id,
                'status' => 1,
                'datetime' => date('Y-m-d H:i:s'),
                'detail' => 'ส่งหนังสือสำเร็จ',
                'book_id' => $book->id
            ]);
            return redirect()->route('bookSender.index')->with('success', 'Book added successfully!');
        }
        return redirect()->route('bookSender.index')->with('error', 'ท่านไม่ได้เลือกไฟล์ที่ต้องการนำเข้าระบบ');
    }
}
