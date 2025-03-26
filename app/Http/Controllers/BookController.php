<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Log_status_book;
use App\Models\Permission;
use App\Models\Position;
use App\Models\User;
use App\Models\Users_permission;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\Auth;
use Webklex\IMAP\Facades\Client;
use Webklex\PHPIMAP\ClientManager;

class BookController extends Controller
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
        if ($this->permission_id != 1 && $this->permission_id != 9) {
            return redirect('/book/show');
        }
        $data['permission_data'] = $this->permission_data;
        $data['function_key'] = __FUNCTION__;
        $books = Book::orderBy('inputBookregistNumber', 'desc')->first();
        if ($books) {
            $data['inputBookregistNumber'] = $books->inputBookregistNumber + 1;
        } else {
            $data['inputBookregistNumber'] = 1;
        }
        return view('book.index', $data);
    }

    public function bookType(Request $request)
    {
        $id = $request->input('id');
        $books = Book::where('selectBookregist', $id)->orderBy('inputBookregistNumber', 'desc')->first();
        if ($books) {
            $inputBookregistNumber = $books->inputBookregistNumber + 1;
        } else {
            $inputBookregistNumber = 1;
        }
        return response()->json($inputBookregistNumber);
    }

    public function save(Request $request)
    {
        if ($request->hasFile('file-input') || isset($request['select-email'])) {
            $book = new Book;
            $book->type = 1;
            $book->selectBookregist = $request['selectBookregist'];
            $book->inputBookregistNumber = $request['inputBookregistNumber'];
            $book->inputBooknumberOrgStruc = $request['inputBooknumberOrgStruc'];
            $book->selectBookcircular = $request['selectBookcircular'];
            $book->inputBooknumberEnd = $request['inputBooknumberEnd'];
            $book->selectLevelSpeed = $request['selectLevelSpeed'];
            $book->inputRecieveDate = $request['inputRecieveDate'];
            $book->inputPickUpDate = $request['inputPickUpDate'];
            $book->inputDated = $request['inputDated'];
            $book->inputSubject = $request['inputSubject'];
            $book->inputBookto = $request['inputBookto'];
            $book->inputBookref = $request['inputBookref'];
            $book->inputContent = $request['inputContent'];
            $book->inputNote = $request['inputNote'];
            $book->selectBookFrom = $request['selectBookFrom'];
            $book->flexCheckChecked = ($request['flexCheckChecked'] == 'on') ? 1 : 0;
            $book->created_by = $this->users->id;
            $book->updated_by = $this->users->id;
            $book->status = 1;
            if (isset($request['select-email'])) {
                $cm = new ClientManager();
                $client = $cm->make([
                    'host'          => 'plaengyao.go.th',
                    'port'          => '993',
                    'encryption'    => 'TLS',
                    'validate_cert' => false,
                    'username'      => 'saraban@plaengyao.go.th',
                    'password'      => 'Saraban@867',
                    'protocol'      => 'imap'
                ]);

                $client->connect();

                $folder = $client->getFolder('INBOX');
                $message = $folder->query()->uid($request['select-email'])->get();
                if ($message) {
                    $message = $message->first();
                    if ($message->hasAttachments()) {
                        $attachments = $message->getAttachments();
                        foreach ($attachments as $attachment) {
                            $filePath =  'uploads/' . time() . '.' . $attachment->getExtension();
                            // $attachment->save(storage_path('app/attachments/' . $newFileName));
                            $attachment->save(storage_path('app/public/uploads/'));
                            rename(storage_path('app/public/uploads/' . $attachment->name), storage_path('app/public/' . $filePath));
                        }
                        $book->file = ($filePath) ? $filePath : '';
                    }
                }
            } else {
                if ($request->hasFile('file-input')) {
                    $file = $request->file('file-input');
                    $filePath = $file->store('uploads');
                    $book->file = ($filePath) ? $filePath : '';
                }
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
                    'detail' => 'นำเข้าเอกสาร',
                    'book_id' => $book->id
                ]);
                return redirect()->route('book.index')->with('success', 'Book added successfully!');
            }
        }
        return redirect()->route('book.index')->with('error', 'ท่านไม่ได้เลือกไฟล์ที่ต้องการนำเข้าระบบ');
    }

    public function show()
    {
        if ($this->permission_id == '3' || $this->permission_id == '4') {
            $data['extends'] = 'book.js.admin';
        } else if ($this->permission_id == '5') {
            $data['extends'] = 'book.js.manager';
        } else if ($this->permission_id == '6') {
            $data['extends'] = 'book.js.bailiff';
        } else if ($this->permission_id == '7') {
            $data['extends'] = 'book.js.mayor_1';
        } else if ($this->permission_id == '8') {
            $data['extends'] = 'book.js.mayor_2';
        } else {
            $data['extends'] = 'book.js.show';
        }
        $data['function_key'] = __FUNCTION__;
        $data['permission_id'] = $this->permission_id;
        $data['position_id'] = $this->position_id;
        $data['position_name'] = $this->position_name;
        $data['permission_data'] = $this->permission_data;
        $data['users'] = $this->users;
        $data['signature'] = $this->signature;
        Session::forget('keyword');
        $book = new Book;
        if ($this->permission_id == '1' || $this->permission_id == '2') {
            $book = $book->select('books.*')->whereIn('status', $this->permission)->orderBy('inputBookregistNumber', 'asc')->limit(5)->get();
        } else {
            if ($this->position_id != null) {
                $book = $book->where('log_status_books.position_id', $this->position_id);
            }
            $book = $book->select('books.*', 'log_status_books.status', 'log_status_books.file', 'log_status_books.position_id')
                ->leftJoin('log_status_books', 'books.id', '=', 'log_status_books.book_id')
                ->whereIn('log_status_books.status', $this->permission)
                ->orderBy('inputBookregistNumber', 'asc')
                ->limit(5)
                ->get();
        }
        foreach ($book as &$rec) {
            $rec->showTime = date('H:i', strtotime($rec->inputRecieveDate));
            $rec->url = url("storage/" . $rec->file);
            $rec->inputBookregistNumber = numberToThaiDigits($rec->inputBookregistNumber);
        }
        $book_count = Book::count();
        $data['totalPages'] = (int)ceil($book_count / 5);
        $data['book'] = $book;
        return view('book.show', $data);
    }

    public function dataList(Request $request)
    {
        $data = [
            'book' => array(),
            'status' => false
        ];
        $pages = $request->input('pages');
        if ($pages != 1) {
            $pages = 5 * ($pages - 1);
        } else {
            $pages = 0;
        }
        $search = session('keyword');
        if (!empty($search)) {
            $query = Book::whereRaw('inputSubject like "%' . $search . '%"')
                ->orWhereRaw('inputBookto like "%' . $search . '%"')
                ->orWhereRaw('inputBookref like "%' . $search . '%"')
                ->orWhereRaw('inputContent like "%' . $search . '%"')
                ->orWhereRaw('inputNote like "%' . $search . '%"');
            if ($this->permission_id == '1' || $this->permission_id == '2') {
                $book = $query->select('books.*')->whereIn('status', $this->permission)->orderBy('inputBookregistNumber', 'asc')->limit(5)->offset($pages)->get();
            } else {
                $query = $query->where('log_status_books.position_id', $this->position_id);
                $book = $query->select('books.*', 'log_status_books.status', 'log_status_books.file')
                    ->leftJoin('log_status_books', 'books.id', '=', 'log_status_books.book_id')
                    ->whereIn('log_status_books.status', $this->permission)
                    ->orderBy('inputBookregistNumber', 'asc')
                    ->limit(5)
                    ->offset($pages)
                    ->get();
            }
        } else {
            $query = new Book;
            if ($this->permission_id == '1' || $this->permission_id == '2') {
                $book = $query->select('books.*')
                    ->whereIn('status', $this->permission)
                    ->orderBy('inputBookregistNumber', 'asc')
                    ->limit(5)
                    ->offset($pages)
                    ->get();
            } else {
                $query = $query->where('log_status_books.position_id', $this->position_id);
                $book = $query->select('books.*', 'log_status_books.status', 'log_status_books.file')
                    ->leftJoin('log_status_books', 'books.id', '=', 'log_status_books.book_id')
                    ->whereIn('log_status_books.status', $this->permission)
                    ->orderBy('inputBookregistNumber', 'asc')
                    ->limit(5)
                    ->offset($pages)
                    ->get();
            }
        }
        if (!empty($book)) {
            foreach ($book as &$rec) {
                $rec->showTime = date('H:i', strtotime($rec->inputRecieveDate));
                $rec->url = url("storage/" . $rec->file);
                $rec->inputBookregistNumber = numberToThaiDigits($rec->inputBookregistNumber);
            }
            $data['book'] = $book;
            $data['status'] = true;
        }

        return response()->json($data);
    }

    public function dataListSearch(Request $request)
    {
        Session::forget('keyword');
        $data = [
            'book' => array(),
            'status' => false
        ];
        $pages = $request->input('pages');
        $search = $request->input('search');
        if ($pages != 1) {
            $pages = 5 * ($pages - 1);
        } else {
            $pages = 0;
        }
        if (!empty($search)) {
            session(['keyword' => $search]);
            $query = Book::whereRaw('(inputSubject like "%' . $search . '%"')
                ->orWhereRaw('inputBookto like "%' . $search . '%"')
                ->orWhereRaw('inputBookref like "%' . $search . '%"')
                ->orWhereRaw('inputContent like "%' . $search . '%"')
                ->orWhereRaw('inputNote like "%' . $search . '%")');
            if ($this->permission_id == '1' || $this->permission_id == '2') {
            } else {
                $query = $query->where('log_status_books.position_id', $this->position_id);
            }
        } else {
            $query = new Book;
            if ($this->permission_id == '1' || $this->permission_id == '2') {
            } else {
                $query = $query->where('log_status_books.position_id', $this->position_id);
            }
        }
        if ($this->permission_id == '1' || $this->permission_id == '2') {
            $book = $query->select('books.*')
                ->whereIn('status', $this->permission)
                ->orderBy('inputBookregistNumber', 'asc')
                ->limit(5)
                ->offset($pages)
                ->get();
        } else {
            $book = $query->select('books.*', 'log_status_books.status', 'log_status_books.file')
                ->leftJoin('log_status_books', 'books.id', '=', 'log_status_books.book_id')
                ->whereIn('log_status_books.status', $this->permission)
                ->orderBy('inputBookregistNumber', 'asc')
                ->limit(5)
                ->offset($pages)
                ->get();
        }
        if (!empty($search)) {
            $query = Book::whereRaw('inputSubject like "%' . $search . '%"')
                ->orWhereRaw('inputBookto like "%' . $search . '%"')
                ->orWhereRaw('inputBookref like "%' . $search . '%"')
                ->orWhereRaw('inputContent like "%' . $search . '%"')
                ->orWhereRaw('inputNote like "%' . $search . '%"');
            if ($this->permission_id == '1' || $this->permission_id == '2') {
            } else {
                $query = $query->where('books.position_id', $this->position_id);
            }
        } else {
            $query = new Book;
            if ($this->permission_id == '1' || $this->permission_id == '2') {
            } else {
                $query = $query->where('books.position_id', $this->position_id);
            }
        }
        if ($this->permission_id == '1' || $this->permission_id == '2') {
            $book_count = $query->whereIn('status', $this->permission)->count();
        } else {
            $book_count = $query->whereIn('log_status_books.status', $this->permission)
                ->leftJoin('log_status_books', 'books.id', '=', 'log_status_books.book_id')
                ->count();
        }
        if (!empty($book)) {
            foreach ($book as &$rec) {
                $rec->showTime = date('H:i', strtotime($rec->inputRecieveDate));
                $rec->url = url("storage/" . $rec->file);
                $rec->inputBookregistNumber = numberToThaiDigits($rec->inputBookregistNumber);
            }
            $data['totalPages'] = (int)ceil($book_count / 5);
            $data['book'] = $book;
            $data['status'] = true;
        }

        return response()->json($data);
    }

    public function save_stamp(Request $request)
    {
        $data['status'] = false;
        $data['message'] = '';
        $id = $request->input('id');
        $positionX = $request->input('positionX');
        $positionY = $request->input('positionY');
        $pages = $request->input('pages');
        $query = new Book;
        $book = $query->where('id', $id)->first();
        if (!empty($book)) {
            $update = Book::find($id);
            $update->status = 2;
            $update->updated_by = $this->users->id;
            $update->updated_at = date('Y-m-d H:i:s');
            if ($update->save()) {
                log_active([
                    'users_id' => auth()->user()->id,
                    'status' => 2,
                    'datetime' => date('Y-m-d H:i:s'),
                    'detail' => 'ลงบันทึกรับหนังสือ',
                    'book_id' => $id
                ]);
                $this->editPdf($positionX, $positionY, $pages, $book);
                $data['status'] = true;
                $data['message'] = 'ลงบันทึกเวลาเรียบร้อยแล้ว';
            }
        }
        return response()->json($data);
    }

    public function editPdf($x, $y, $pages, $data)
    {
        $pdf = new Fpdi();
        $filePath = public_path('/storage/' . $data->file);

        if (!file_exists($filePath)) {
            return 'File not found!';
        }

        $pdf = new Fpdi();

        $pageCount = $pdf->setSourceFile($filePath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $pdf->AddPage();
            $pdf->useTemplate($templateId);

            if ($pageNo == $pages) {

                $fontPath = resource_path('fonts/sarabunextralight.php');
                $pdf->AddFont('sarabunextralight', '', $fontPath);
                $pdf->setTextColor(0, 0, 255);
                $pdf->setDrawColor(0, 0, 255);
                $x = ($x / 1.5) * 0.3528;
                $y = ($y / 1.5) * 0.3528;
                $width = 50;
                $height = 27;
                $pdf->Rect($x, $y, $width, $height);
                $pdf->SetFont('sarabunextralight', '', 10);
                $pdf->Text($x + 1, $y + 2, 'องค์การบริหารส่วนตำบลแปลงยาว');
                $pdf->Text($x + 21, $y + 8.5, numberToThaiDigits($data->inputBookregistNumber));
                $pdf->SetFont('sarabunextralight', '', 8);
                $pdf->Text($x + 1, $y + 10, 'รับที่.................................................................');
                $pdf->Text($x + 8, $y + 15, convertDayToThai($data->inputRecieveDate));
                $pdf->Text($x + 19.5, $y + 15, convertMonthsToThai($data->inputRecieveDate));
                $pdf->Text($x + 39, $y + 15, convertYearsToThai($data->inputRecieveDate));
                $pdf->Text($x + 1, $y + 16, 'วันที่...........เดือน........................พ.ศ..............');
                $pdf->Text($x + 19, $y + 20, convertTimeToThai(date('H:i:s', strtotime($data->inputRecieveDate))));
                $pdf->Text($x + 1, $y + 21, 'เวลา.............................................................น.');
            }
        }

        $outputPath = public_path('/storage/' . $data->file);
        $pdf->Output($outputPath, 'F');
    }


    public function send_to_admin(Request $request)
    {
        $data['status'] = false;
        $data['message'] = '';
        $id = $request->input('id');
        $position_id = $request->input('position_id');
        $query = new Book;
        $book = $query->where('id', $id)->first();
        if (!empty($book)) {
            $update = Book::find($id);
            $update->status = 3;
            $update->updated_by = $this->users->id;
            $update->updated_at = date('Y-m-d H:i:s');
            foreach ($position_id as $value) {
                $filePath = storage_path('app/public/' . $book->file);
                $insert = new Log_status_book();
                $destinationDirectory = storage_path('app/public/' . $value . '/' . $book->file);
                if (!File::exists(storage_path('app/public/' . $value . '/uploads'))) {
                    File::makeDirectory(storage_path('app/public/' . $value . '/uploads'), 0777, true); // สร้างโฟลเดอร์ใหม่
                }
                if (File::exists($filePath)) {
                    File::copy($filePath, $destinationDirectory);
                }
                $insert->book_id = $id;
                $insert->status = 3;
                $insert->datetime = date('Y-m-d H:i:s');
                $insert->file = $value . '/' . $book->file;
                $insert->position_id = $value;
                if ($insert->save()) {
                    $sql = Position::where('id', $value)->first();
                    log_active([
                        'users_id' => auth()->user()->id,
                        'status' => 3,
                        'datetime' => date('Y-m-d H:i:s'),
                        'detail' => 'แทงเรื่องไป ' . $sql->position_name,
                        'book_id' => $id,
                        'position_id' => $value
                    ]);
                }
            }
            if ($update->save()) {
                $data['status'] = true;
                $data['message'] = 'แทงเรื่องเรียบร้อยแล้ว';
            }
        }
        return response()->json($data);
    }

    public function admin_stamp(Request $request)
    {
        $data['status'] = false;
        $data['message'] = '';
        $id = $request->input('id');
        $positionX = $request->input('positionX');
        $positionY = $request->input('positionY');
        $pages = $request->input('pages');
        $query = new Book;
        $book = $query->where('id', $id)->first();
        if (!empty($book)) {
            $update = Log_status_book::where('position_id', $this->position_id)->where('book_id', $id)->first();
            $update->status = 3.5;
            $update->updated_at = date('Y-m-d H:i:s');
            $update->adminBookNumber = adminNumber();
            $update->adminDated = date('Y-m-d H:i:s');
            if ($update->save()) {
                $data = [
                    'adminBookNumber' => adminNumber(),
                    'adminDated' => date('Y-m-d H:i:s'),
                    'file' => $book->file
                ];
                $this->editPdf_admin($positionX, $positionY, $pages, $update);
                log_active([
                    'users_id' => auth()->user()->id,
                    'status' => 3.5,
                    'datetime' => date('Y-m-d H:i:s'),
                    'detail' => 'ประทับตราลงรับ',
                    'book_id' => $id,
                    'position_id' => $update->position_id
                ]);
                $data['status'] = true;
                $data['message'] = 'ลงบันทึกเวลาเรียบร้อยแล้ว';
            }
        }
        return response()->json($data);
    }

    public function editPdf_admin($x, $y, $pages, $data)
    {

        $permission_name = $this->position_name;
        $text = mb_strlen($permission_name);
        if ($text >= 30) {
            $dynamicX = 0.5;
        } else if ($text >= 20) {
            $dynamicX = 2;
        } else if ($text >= 15) {
            $dynamicX = 12;
        } else if ($text >= 13) {
            $dynamicX = 16.5;
        } else if ($text >= 10) {
            $dynamicX = 14;
        } else {
            $dynamicX = 17.5;
        }
        $pdf = new Fpdi();
        $filePath = public_path('/storage/' . $data['file']);

        if (!file_exists($filePath)) {
            return 'File not found!';
        }

        $pdf = new Fpdi();

        $pageCount = $pdf->setSourceFile($filePath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $pdf->AddPage();
            $pdf->useTemplate($templateId);

            if ($pageNo == $pages) {

                $fontPath = resource_path('fonts/sarabunextralight.php');
                $pdf->AddFont('sarabunextralight', '', $fontPath);
                $pdf->setTextColor(0, 0, 255);
                $pdf->setDrawColor(0, 0, 255);
                $x = ($x / 1.5) * 0.3528;
                $y = ($y / 1.5) * 0.3528;
                $width = 50;
                $height = 27;
                $pdf->Rect($x, $y, $width, $height);
                $pdf->SetFont('sarabunextralight', '', 10);
                $pdf->Text($x + $dynamicX, $y + 2, $permission_name);
                $pdf->Text($x + 21, $y + 8.5, numberToThaiDigits($data['adminBookNumber']));
                $pdf->SetFont('sarabunextralight', '', 8);
                $pdf->Text($x + 1, $y + 10, 'รับที่.................................................................');
                $pdf->Text($x + 8, $y + 15, convertDayToThai($data['adminDated']));
                $pdf->Text($x + 19.5, $y + 15, convertMonthsToThai($data['adminDated']));
                $pdf->Text($x + 39, $y + 15, convertYearsToThai($data['adminDated']));
                $pdf->Text($x + 1, $y + 16, 'วันที่...........เดือน........................พ.ศ..............');
                $pdf->Text($x + 19, $y + 20, convertTimeToThai(date('H:i:s', strtotime($data['adminDated']))));
                $pdf->Text($x + 1, $y + 21, 'เวลา.............................................................น.');
            }
        }

        $outputPath = public_path('/storage/' . $data['file']);
        $pdf->Output($outputPath, 'F');
    }

    public function checkbox_send()
    {
        $txt = '<div class="row d-flex align-items-start">';
        $get_users = Users_permission::select('users.*', 'permissions.permission_name')
            ->leftJoin('users', 'users_permissions.users_id', '=', 'users.id')
            ->leftJoin('permissions', 'permissions.id', '=', 'users_permissions.permission_id')
            ->where('users_permissions.position_id', $this->position_id)
            ->where('users_permissions.permission_id', $this->permission_data->parent_id)
            ->get();
        $count = User::where('permission_id', $this->permission_data->parent_id)->where('position_id', $this->position_id)->count();
        if (!empty($get_users)) {
            for ($i = 0; $i < $count; $i++) {
                $txt .= '<div class="col-1 mb-3"></div><div class="col-11 mb-2">';
                $txt .= '<input type="checkbox" name="flexCheckChecked[]" id="flexCheckChecked' . $get_users[$i]->id . '" value="' . $get_users[$i]->id . '" class="form-check-input"><label style="margin-left:5px;" for="flexCheckChecked' . $get_users[$i]->id . '">' . $get_users[$i]->fullname . ' (' . $get_users[$i]->permission_name . ')' . '</label>';
                $txt .= '</div>';
            }
        }
        $txt .= '</div>';
        return response()->json($txt);
    }

    public function _checkbox_send()
    {
        $txt = '<div class="row d-flex align-items-start">';
        $get_users = User::select('users.*', 'permissions.permission_name')
            ->join('permissions', 'permissions.id', '=', 'users.permission_id')
            ->where('permission_id', $this->permission_data->parent_id)
            ->get();
        $count = User::where('permission_id', $this->permission_data->parent_id)->count();
        if (!empty($get_users)) {
            for ($i = 0; $i < $count; $i++) {
                $txt .= '<div class="col-1 mb-3"></div><div class="col-11 mb-2">';
                $txt .= '<input type="checkbox" name="flexCheckChecked[]" id="flexCheckChecked' . $get_users[$i]->id . '" value="' . $get_users[$i]->id . '" class="form-check-input"><label style="margin-left:5px;" for="flexCheckChecked' . $get_users[$i]->id . '">' . $get_users[$i]->fullname . ' (' . $get_users[$i]->permission_name . ')' . '</label>';
                $txt .= '</div>';
            }
        }
        $txt .= '</div>';
        return response()->json($txt);
    }


    public function send_to_save(Request $request)
    {
        $data['status'] = false;
        $data['message'] = '';
        $id = $request->input('id');
        $users_id = $request->input('users_id');
        $position_id = $request->input('position_id');
        $status = $request->input('status');
        $query = new Book;
        $book = $query->where('id', $id)->first();
        if (!empty($book)) {
            $update = Log_status_book::where('position_id', $position_id)->where('book_id', $id)->first();
            if ($status == 14) {
                $getForward = User::where('permission_id', 4)->where('position_id', $position_id)->first();
                $update->parentUsers = $getForward->id;
            } else {
                $getForward = User::find($users_id);
                $sql = Permission::where('id', $getForward->permission_id)->first();
                $update->parentUsers = $users_id;
            }
            $update->status = $status;
            $update->updated_at = date('Y-m-d H:i:s');
            if ($update->save()) {
                if ($status == 14) {
                    $detail = 'ส่งเวียนหนังสือ';
                } else {
                    $detail = 'แทงเรื่อง (' . $getForward->fullname . '(' . $sql->permission_name . '))';
                }
                log_active([
                    'users_id' => auth()->user()->id,
                    'status' => $status,
                    'datetime' => date('Y-m-d H:i:s'),
                    'detail' => $detail,
                    'book_id' => $id,
                    'position_id' => $update->position_id
                ]);
                $data['status'] = true;
                $data['message'] = 'แทงเรื่องเรียบร้อยแล้ว';
            }
        }
        return response()->json($data);
    }

    public function confirm_signature(Request $request)
    {
        $data = [
            'status' => false,
            'message' => 'กรุณากรอกรหัสผ่านเกษียณ'
        ];
        $password = $request->input('modal-Password');
        if (!empty($password)) {
            $users = new User;
            $users = $users->where('id', $this->users->id)->first();
            if ($users) {
                $verify_password = password_verify($password, $users->password);
                if ($verify_password) {
                    $data = [
                        'status' => true,
                        'message' => 'ยืนยันรหัสผ่านเกษียณเรียบร้อยแล้ว'
                    ];
                } else {
                    $data['message'] = 'รหัสผ่านเกษียณไม่ถูกต้อง';
                }
            }
        }
        return response()->json($data);
    }

    public function signature_stamp(Request $request)
    {
        $data['status'] = false;
        $data['message'] = '';
        $input = $request->input();
        $book = Book::where('id', $input['id'])->first();
        if (!empty($book)) {
            $update = Log_status_book::where('position_id', $this->position_id)->where('book_id', $input['id'])->first();
            if ($update->status == 3.5) {
                $status = 4;
            } else {
                $status = 5;
            }
            $update->status = $status;
            $update->updated_at = date('Y-m-d H:i:s');
            if ($update->save()) {
                log_active([
                    'users_id' => auth()->user()->id,
                    'status' => $status,
                    'datetime' => date('Y-m-d H:i:s'),
                    'detail' => 'เกษียณหนังสือ',
                    'book_id' => $input['id'],
                    'position_id' => $update->position_id
                ]);
                $this->editPdf_signature($input['positionX'], $input['positionY'], $input['pages'], $update, $input['text'], $input['checkedValues']);
                $data['status'] = true;
                $data['message'] = 'ลงบันทึกเกษียณหนังสือเรียบร้อย';
            }
        }
        return response()->json($data);
    }

    public function editPdf_signature($x, $y, $pages, $data, $text, $checkedValues)
    {
        error_reporting(E_ALL & ~E_WARNING);
        preg_match_all('/\n/', $text, $matches);
        $lineCount = count($matches[0]);
        $text = explode("\n", $text);
        $pdf = new Fpdi();
        $filePath = public_path('/storage/' . $data['file']);

        if (!file_exists($filePath)) {
            return 'File not found!';
        }

        $pdf = new Fpdi();

        $pageCount = $pdf->setSourceFile($filePath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $pdf->AddPage();
            $pdf->useTemplate($templateId);

            if ($pageNo == $pages) {

                $fontPath = resource_path('fonts/sarabunextralight.php');
                $pdf->AddFont('sarabunextralight', '', $fontPath);
                $pdf->setTextColor(0, 0, 255);
                $pdf->setDrawColor(0, 0, 255);
                $x = ($x / 1.5) * 0.3528;
                $y = ($y / 1.5) * 0.3528;
                $pdf->SetFont('sarabunextralight', '', 10);
                for ($i = 0; $i <= $lineCount; $i++) {
                    $pdf->Text($x, $y + (5 * $i), $text[$i]);
                }

                $checkbox_text = '';
                $checkbox_x = 0;
                foreach ($checkedValues as $key => $value) {
                    if ($checkedValues == 4) {
                        $plus_y = 35;
                    } else {
                        $plus_y = 5;
                    }
                }
                foreach ($checkedValues as $key => $value) {
                    switch ($value) {
                        case '1':
                            $checkbox_text = '(' . $this->users->fullname . ')';
                            break;
                        case '2':
                            $checkbox_text = $this->permission_data->permission_name;
                            break;
                        case '3':
                            $checkbox_text = convertDateToThai(date("Y-m-d"));
                            break;
                    }
                    $pdf->Text($x, $y + $plus_y + (5 * $lineCount) + (5 * $key), $checkbox_text);
                }
                $pdf->Image(public_path('storage/users/' . auth()->user()->signature), $x - 13, $y + 3 + (5 * $lineCount), 65, 30);
            }
        }

        $outputPath = public_path('/storage/' . $data->file);
        $pdf->Output($outputPath, 'F');
    }

    public function manager_stamp(Request $request)
    {
        $data['status'] = false;
        $data['message'] = '';
        $input = $request->input();
        $book = Book::where('id', $input['id'])->first();
        if (!empty($book)) {
            $update = Log_status_book::where('position_id', $input['position_id'])->where('book_id', $input['id'])->first();
            $update->status = $input['status'];
            $update->updated_at = date('Y-m-d H:i:s');
            if ($update->save()) {
                log_active([
                    'users_id' => auth()->user()->id,
                    'status' => $input['status'],
                    'datetime' => date('Y-m-d H:i:s'),
                    'detail' => 'เซ็นเกษียณหนังสือ',
                    'book_id' => $input['id'],
                    'position_id' => $update->position_id
                ]);
                $this->editPdf_signature($input['positionX'], $input['positionY'], $input['pages'], $update, $input['text'], $input['checkedValues']);
                $data['status'] = true;
                $data['message'] = 'ลงบันทึกลายเซ็นเรียบร้อยแล้ว';
            }
        }
        return response()->json($data);
    }

    public function getEmail()
    {
        $cm = new ClientManager();
        $client = $cm->make([
            'host'          => 'plaengyao.go.th',
            'port'          => '993',
            'encryption'    => 'TLS',
            'validate_cert' => false,
            'username'      => 'saraban@plaengyao.go.th',
            'password'      => 'Saraban@867',
            'protocol'      => 'imap'
        ]);

        $client->connect();

        $folder = $client->getFolder('INBOX');
        $totalMessages = $folder->query()->all()->count();
        $messages = $folder->query()->all()->get();

        $data = [
            'data' => [],
            'status' => false,
            'total' => $totalMessages
        ];
        if ($totalMessages > 0) {
            foreach ($messages as $message) {
                $uid = $message->getUid();
                $item['uid'] = $uid . '';
                $item['title'] = $message->getSubject() . '';
                $item['sender'] = $message->getFrom() . '';
                $item['date'] = DateThai($message->getDate() . '');
                if ($message->hasAttachments()) {
                    $attachments = $message->getAttachments();

                    foreach ($attachments as $attachment) {
                        $url = 'https://webmail.plaengyao.go.th/roundcube/?_task=mail&_frame=1&_mbox=INBOX&_uid=' . $uid . '&_part=2&_action=get&_extwin=1';
                        $item['url'] = '<a href="' . $url . '" target="_blank"><i class="fa fa-envelope-open-o"></i></a>';
                    }
                }
                $data['data'][] = $item;
            }
            $data['status'] = true;
        }
        return response()->json($data);
    }

    function uploadPdf(Request $request)
    {
        $data['status'] = false;
        $data['message'] = '';
        $input = $request->input();
        $book = book::find($input['id']);
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $file->store('uploads');
            $book->file = ($filePath) ? $filePath : '';
            if ($book->save()) {
                $data['status'] = true;
                $data['message'] = 'บันทึกข้อมูลสำเร็จ';
            }
        }
        return response()->json($data);
    }

    public function number_save(Request $request)
    {
        $id = $request->input('id');
        $positionX = $request->input('positionX');
        $positionY = $request->input('positionY');
        $pages = $request->input('pages');
        $query = new Book;
        $book = $query->where('id', $id)->first();
        if (!empty($book)) {
            $update = Book::find($id);
            $update->is_number_stamp = 1;
            $update->status = 2;
            $update->updated_by = $this->users->id;
            $update->updated_at = date('Y-m-d H:i:s');
            if ($update->save()) {
                log_active([
                    'users_id' => auth()->user()->id,
                    'status' => 1,
                    'datetime' => date('Y-m-d H:i:s'),
                    'detail' => 'ลงประทับเลขที่จอง',
                    'book_id' => $id
                ]);
                $this->editNumberPDF($positionX, $positionY, $pages, $book);
                $data['status'] = true;
                $data['message'] = 'ลงบันทึกเวลาเรียบร้อยแล้ว';
            }
        }
        return response()->json($data);
    }

    public function editNumberPDF($x, $y, $pages, $data)
    {
        $pdf = new Fpdi();
        $filePath = public_path('/storage/' . $data->file);

        if (!file_exists($filePath)) {
            return 'File not found!';
        }

        $pdf = new Fpdi();

        $pageCount = $pdf->setSourceFile($filePath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $pdf->AddPage();
            $pdf->useTemplate($templateId);

            if ($pageNo == $pages) {

                $fontPath = resource_path('fonts/sarabunextralight.php');
                $pdf->AddFont('sarabunextralight', '', $fontPath);
                $pdf->setTextColor(0, 0, 255);

                $x = ($x / 1.5) * 0.3528;
                $y = ($y / 1.5) * 0.3528;
                $pdf->SetFont('sarabunextralight', '', 14);
                $pdf->Text($x, $y - 5, numberToThaiDigits($data->inputBookregistNumber));
            }
        }

        $outputPath = public_path('/storage/' . $data->file);
        $pdf->Output($outputPath, 'F');
    }
}
