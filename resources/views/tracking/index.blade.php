@extends('include.main')
@section('style')
<style>
    body {
        font-family: 'Noto Sans Thai';
    }
</style>
@endsection

@section('content')
<div class="col-12 d-flex justify-content-center">
    <div class="card w-75 mt-5">
        <div class="card-body">
            <table id="example" class="display">
                <thead>
                    <tr>
                        <th>เลขที่</th>
                        <th>เลขที่หนังสือ</th>
                        <th>เรื่อง</th>
                        <th>วันที่</th>
                        <th></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@endsection

@extends('tracking.js.index')