<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Permission;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
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
            $this->position_name = $sql->position_name;
            return $next($request);
        });
    }
    public function index()
    {
        $data['function_key'] = __FUNCTION__;
        $books = Book::orderBy('inputBookregistNumber', 'desc')->first();
        if ($books) {
            $data['inputBookregistNumber'] = $books->inputBookregistNumber + 1;
        } else {
            $data['inputBookregistNumber'] = 1;
        }
        return view('book.index', $data);
    }

    public function save(Request $request)
    {
        $book = new Book;
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
        if ($request->hasFile('file-input')) {
            $file = $request->file('file-input');
            $filePath = $file->store('uploads');
        }
        if ($request->hasFile('file-attachments')) {
            $attachments = $request->file('file-attachments');
            $filePathAttachments = $attachments->store('uploads');
        }
        $book->file = ($filePath) ? $filePath : '';
        $book->fileAttachments = ($filePathAttachments) ? $filePathAttachments : '';
        if ($book->save()) {
            return redirect()->route('book.index')->with('success', 'Book added successfully!');
        }
    }

    public function show()
    {
        if ($this->permission_id == '1') {
            $data['extends'] = 'book.js.show';
        } else if ($this->permission_id == '2') {
            $data['extends'] = 'book.js.admin';
        } else if ($this->permission_id == '3') {
            $data['extends'] = 'book.js.manager';
        } else if ($this->permission_id == '4') {
            $data['extends'] = 'book.js.bailiff';
        } else if ($this->permission_id == '5') {
            $data['extends'] = 'book.js.mayor_1';
        } else if ($this->permission_id == '6') {
            $data['extends'] = 'book.js.mayor_2';
        }
        $data['function_key'] = __FUNCTION__;
        $data['permission_id'] = $this->permission_id;
        $data['position_id'] = $this->position_id;
        $data['position_name'] = $this->position_name;
        $data['permission_data'] = $this->permission_data;
        $data['users'] = $this->users;
        Session::forget('keyword');
        $book = new Book;
        if ($this->permission_id == '2') {
            $book = $book->where('books.position_id', $this->position_id);
        }
        $book = $book->select('books.*', 'users.fullname')->whereIn('status', $this->permission)->orderBy('inputBookregistNumber', 'asc')->join('users', 'books.selectBookFrom', '=', 'users.id')->limit(5)->get();
        foreach ($book as &$rec) {
            $rec->showTime = date('H:i', strtotime($rec->created_at));
            $rec->url = url("storage/" . $rec->file);
        }
        $book_count = Book::join('users', 'books.selectBookFrom', '=', 'users.id')->count();
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
            if ($this->permission_id == '2') {
                $query = $query->where('books.position_id', $this->position_id);
            }
            $book = $query->select('books.*', 'users.fullname')->whereIn('status', $this->permission)->orderBy('inputBookregistNumber', 'asc')->join('users', 'books.selectBookFrom', '=', 'users.id')->limit(5)->offset($pages)->get();
        } else {
            $query = new Book;
            if ($this->permission_id == '2') {
                $query = $query->where('books.position_id', $this->position_id);
            }
            $book = $query->select('books.*', 'users.fullname')->whereIn('status', $this->permission)->orderBy('inputBookregistNumber', 'asc')->join('users', 'books.selectBookFrom', '=', 'users.id')->limit(5)->offset($pages)->get();
        }
        if (!empty($book)) {
            foreach ($book as &$rec) {
                $rec->showTime = date('H:i', strtotime($rec->created_at));
                $rec->url = url("storage/" . $rec->file);
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
            if ($this->permission_id == '2') {
                $query = $query->where('books.position_id', $this->position_id);
            }
        } else {
            $query = new Book;
            if ($this->permission_id == '2') {
                $query = $query->where('books.position_id', $this->position_id);
            }
        }
        $book = $query->select('books.*', 'users.fullname')->whereIn('status', $this->permission)->orderBy('inputBookregistNumber', 'asc')->join('users', 'books.selectBookFrom', '=', 'users.id')->limit(5)->offset($pages)->get();
        if (!empty($search)) {
            $query = Book::whereRaw('inputSubject like "%' . $search . '%"')
                ->orWhereRaw('inputBookto like "%' . $search . '%"')
                ->orWhereRaw('inputBookref like "%' . $search . '%"')
                ->orWhereRaw('inputContent like "%' . $search . '%"')
                ->orWhereRaw('inputNote like "%' . $search . '%"');
            if ($this->permission_id == '2') {
                $query = $query->where('books.position_id', $this->position_id);
            }
        } else {
            $query = new Book;
            if ($this->permission_id == '2') {
                $query = $query->where('books.position_id', $this->position_id);
            }
        }
        $book_count = $query->whereIn('status', $this->permission)->join('users', 'books.selectBookFrom', '=', 'users.id')->count();
        if (!empty($book)) {
            foreach ($book as &$rec) {
                $rec->showTime = date('H:i', strtotime($rec->created_at));
                $rec->url = url("storage/" . $rec->file);
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
            $update->position_id = $position_id;
            $update->updated_by = $this->users->id;
            $update->updated_at = date('Y-m-d H:i:s');
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
            $update = Book::find($id);
            $update->status = 4;
            $update->updated_by = $this->users->id;
            $update->updated_at = date('Y-m-d H:i:s');
            $update->adminBookNumber = adminNumber();
            $update->adminDated = date('Y-m-d H:i:s');
            if ($update->save()) {
                $data = [
                    'adminBookNumber' => adminNumber(),
                    'adminDated' => date('Y-m-d H:i:s'),
                    'file' => $book->file
                ];
                $this->editPdf_admin($positionX, $positionY, $pages, $data);
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
        $txt = '';
        $get_users = User::select('users.*', 'permissions.permission_name')
            ->where('permission_id', $this->permission_data->parent_id)
            ->join('permissions', 'permissions.id', '=', 'users.permission_id')
            ->where('position_id', $this->position_id)
            ->get();
        $count = User::where('permission_id', $this->permission_data->parent_id)->where('position_id', $this->position_id)->count();
        if (!empty($get_users)) {
            for ($i = 0; $i < $count; $i++) {
                if ($i > 0) {
                    $txt .= '<br>';
                }
                $txt .= '<div><input type="checkbox" name="flexCheckChecked[]" id="flexCheckChecked' . $get_users[$i]->id . '" value="' . $get_users[$i]->id . '" class="form-check-input"><label for="flexCheckChecked' . $get_users[$i]->id . '">' . $get_users[$i]->fullname . ' (' . $get_users[$i]->permission_name . ')' . '</label></div>';
            }
        }
        return response()->json($txt);
    }


    public function send_to_save(Request $request)
    {
        $data['status'] = false;
        $data['message'] = '';
        $id = $request->input('id');
        $users_id = $request->input('users_id');
        $status = $request->input('status');
        $query = new Book;
        $book = $query->where('id', $id)->first();
        if (!empty($book)) {
            $update = Book::find($id);
            $update->status = $status;
            $update->parentUsers = $users_id;
            $update->updated_by = $this->users->id;
            $update->updated_at = date('Y-m-d H:i:s');
            if ($update->save()) {
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
            $update = Book::find($input['id']);
            $update->status = 5;
            $update->updated_by = $this->users->id;
            $update->updated_at = date('Y-m-d H:i:s');
            if ($update->save()) {
                $this->editPdf_signature($input['positionX'], $input['positionY'], $input['pages'], $book, $input['text'], $input['checkedValues']);
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
                    switch ($value) {
                        case '1':
                            $checkbox_text = '(' . $this->users->fullname . ')';
                            $checkbox_x = 0;
                            break;
                        case '2':
                            $checkbox_text = $this->permission_data->permission_name;
                            $checkbox_x = -8;
                            break;
                        case '3':
                            $checkbox_text = convertDateToThai(date("Y-m-d"));
                            $checkbox_x = +1;
                            break;
                    }
                    $pdf->Text($x - $checkbox_x, $y + 35 + (5 * $lineCount) + (5 * $key), $checkbox_text);
                }
                $pdf->Image(public_path('storage/uploads/users/signature.png'), $x - 13, $y + 3 + (5 * $lineCount), 65, 30);
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
            $update = Book::find($input['id']);
            $update->status = $input['status'];
            $update->updated_by = $this->users->id;
            $update->updated_at = date('Y-m-d H:i:s');
            if ($update->save()) {
                $this->editPdf_SignatureAll($input['positionX'], $input['positionY'], $input['pages'], $book);
                $data['status'] = true;
                $data['message'] = 'ลงบันทึกลายเซ็นเรียบร้อยแล้ว';
            }
        }
        return response()->json($data);
    }

    public function editPdf_SignatureAll($x, $y, $pages, $data)
    {
        error_reporting(E_ALL & ~E_WARNING);
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

                $pdf->Text($x, $y + 35 + (5 * 0), '(' . $this->users->fullname . ')');
                $pdf->Text($x + 3, $y + 35 + (5 * 1), $this->permission_data->permission_name);
                $pdf->Text($x - 1, $y + 35 + (5 * 2), convertDateToThai(date("Y-m-d")));
                $pdf->Image(public_path('storage/uploads/users/signature.png'), $x - 13, $y + 3, 65, 30);
            }
        }

        $outputPath = public_path('/storage/' . $data->file);
        $pdf->Output($outputPath, 'F');
    }

    public function getEmail()
    {
        $cm = new ClientManager();
        $client = $cm->make([
            'host'          => env('IMAP_HOST'),
            'port'          => env('IMAP_PORT'),
            'encryption'    => env('IMAP_ENCRYPTION'),
            'validate_cert' => env('IMAP_VALIDATE_CERT'),
            'username'      => env('IMAP_USERNAME'),
            'password'      => env('IMAP_PASSWORD'),
            'protocol'      => 'imap'
        ]);

        $client->connect();

        $folder = $client->getFolder('INBOX');
        $messages = $folder->query()->all()->get();

        foreach ($messages as $message) {
            dd($message);
            echo $message->getSubject() . '<br>';
            echo $message->getTextBody() . '<br>';
            if ($message->hasAttachments()) {
                $attachments = $message->getAttachments();

                foreach ($attachments as $attachment) {
                    // $attachment->save(storage_path('app/attachments/'));
                    echo 'Attachment saved: ' . $attachment->name . '<br>';
                }
            } else {
                echo 'No attachments.<br>';
            }
        }
    }
}
