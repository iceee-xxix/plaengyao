<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UsersController extends Controller
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
    public function listUsers()
    {
        if ($this->permission_id != 9) {
            return redirect('/book/show');
        }
        $data['permission_data'] = $this->permission_data;
        $data['function_key'] = __FUNCTION__;
        return view('users.index', $data);
    }

    public function listData()
    {
        $users = User::select('users.*', 'permissions.permission_name', 'positions.position_name')
            ->whereNot('permission_id', '9')
            ->leftJoin('permissions', 'users.permission_id', '=', 'permissions.id')
            ->leftJoin('positions', 'users.position_id', '=', 'positions.id')
            ->orderBy('users.id', 'asc')
            ->get();
        foreach ($users as $key => $value) {
            $value->action = '<a href="' . url('/users/edit/' . $value->id) . '" class="btn btn-sm btn-outline-primary"><i class="fa fa-edit"></i></a>';
        }
        $data['data'] = $users;
        return response()->json($data);
    }

    public function edit($id)
    {
        if ($this->permission_id != 9) {
            if ($id != auth()->user()->id) {
                return redirect('/users/edit/' . auth()->user()->id);
            }
        }
        $data['permission_data'] = $this->permission_data;
        $data['function_key'] = 'listUsers';
        $users = User::find($id);
        $data['data'] = $users;
        return view('users.edit', $data);
    }

    public function save(Request $request)
    {
        $input = $request->input();
        $users = User::find($input['id']);
        if ($users) {
            $users->fullname = $input['fullname'];
            if (isset($input['email'])) {
                $users->username = $input['email'];
            }
            if (!empty($input['password'])) {
                $users->password_email = $input['password'];
            }
            if (!empty($input['passwordLogin'])) {
                $users->password = password_hash($input['passwordLogin'], PASSWORD_DEFAULT);
            }
            $users->updated_at = date('Y-m-d H:i:s');
            if ($request->hasFile('formFile')) {
                $file = $request->file('formFile');
                $filePath = $file->store('users');
                $filePath = str_replace('users/', '', $filePath);
                $users->signature = $filePath;
            }
            if ($users->save()) {
                if ($input['id'] != auth()->user()->id) {
                    return redirect()->route('users.listUsers')->with('success', 'แก้ไขข้อมูลสำเร็จ');
                } else {
                    return redirect('/users/edit/' . $input['id'])->with('success', 'แก้ไขข้อมูลสำเร็จ');
                }
            }
        }
    }
}
