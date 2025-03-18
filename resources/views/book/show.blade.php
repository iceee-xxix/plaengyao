@extends('include.main')
@section('style')
<style>
    body {
        font-family: 'Noto Sans Thai';
    }


    h3 {
        font-weight: normal;
        font-size: 18px;
        margin-bottom: 10px;
    }

    #pdf-container {
        height: calc(100vh - 108px);
        width: 90%;
        background-color: #fff;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        margin: 0px auto;
        overflow-y: auto;
    }

    #pdf-container canvas {
        margin-bottom: 20px;
        border: 1px solid #ccc;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .hidden {
        display: none;
    }

    span.req {
        color: red;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="row">
            <div class="col-3">
                <div class="input-group mb-3">
                    <input type="text" id="inputSearch" class="form-control border-dark" placeholder="ค้นหา">
                    <button class="btn btn-outline-dark" type="button" id="search_btn">Button</button>
                </div>
            </div>
            <div class="col-9">
                <div class="row">
                    <div class="col-6 d-flex align-items-center">
                        <label style="color:red" id="txt_label"></label>
                    </div>
                    <div class="col-6 d-flex justify-content-end mb-3">
                        <button class="btn btn-outline-dark btn-sm hidden btn-default" style="margin-right: 5px;font-size: 15px;" id="add-stamp" title="ตราประทับ">ตราประทับ</button>
                        <button class="btn btn-outline-dark btn-sm hidden btn-default" style="margin-right: 5px;font-size: 15px;" id="save-stamp" title="บันทึก" disabled><i class="fa fa-floppy-o"></i></button>
                        <button class="btn btn-outline-dark btn-sm hidden btn-default" style="margin-right: 5px;font-size: 15px;" id="send-to" title="แทงเรื่อง"><i class="fa fa-send-o"></i></button>
                        <button class="btn btn-outline-dark btn-sm hidden btn-default" style="margin-right: 5px;font-size: 15px;" id="send-signature" title="เกษียณหนังสือ" disabled><i class="fa fa-edit"></i></button>
                        <button class="btn btn-outline-dark btn-sm hidden btn-default" style="margin-right: 5px;font-size: 15px;" id="signature-save" title="บันทึกข้อมูล" disabled><i class="fa fa-floppy-o"></i></button>
                        <button class="btn btn-outline-dark btn-sm hidden btn-default" style="margin-right: 5px;font-size: 15px;" id="manager-sinature" title="เซ็นหนังสือ" disabled><i class="fa fa-edit"></i></i></button>
                        <button class="btn btn-outline-dark btn-sm hidden btn-default" style="margin-right: 5px;font-size: 15px;" id="manager-save" title="บันทึกข้อมูล" disabled><i class="fa fa-floppy-o"></i></button>
                        <button class="btn btn-outline-dark btn-sm hidden btn-default" style="margin-right: 5px;font-size: 15px;" id="manager-send" title="แทงเรื่อง"><i class="fa fa-send-o"></i></button>
                        <button class="btn btn-outline-dark btn-sm hidden btn-default" style="margin-right: 5px;font-size: 15px;" id="send-save" title="บันทึกข้อมูล" disabled><i class="fa fa-floppy-o"></i></button>
                        <button class="btn btn-outline-dark btn-sm" style="margin-right: 5px;font-size: 5px;" id="prev"><i class="fa fa-arrow-circle-left"></i></button>
                        <select id="page-select" class="border-dark"></select>
                        <button class="btn btn-outline-dark btn-sm" style="margin-left: 5px;font-size: 5px;" id="next"><i class="fa fa-arrow-circle-right"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-3">
        <div id="box-card-item" style="height: 770px;overflow: auto;">
            @foreach($book as $rec)
            <a href="javascript:void(0)" onclick="openPdf('{{$rec->url}}','{{$rec->id}}','{{$rec->status}}')">
                <div class="card border-dark mb-2">
                    <div class="card-header text-dark fw-bold">{{$rec->inputSubject}}</div>
                    <div class="card-body text-dark">
                        <div class="row">
                            <div class="col-9">{{ $rec->fullname }}</div>
                            <div class="col-3 fw-bold">{{ $rec->showTime }} น.</div>
                        </div>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        <div class="d-flex justify-content-end mt-2">
            <button class="btn btn-outline-dark btn-sm" style="margin-right: 5px;font-size: 5px;" id="prevPage"><i class="fa fa-arrow-circle-left"></i></button>
            <select id="page-select-card" class="border-dark">
                @for($page = 1; $page <= $totalPages; $page++)
                    <option value="{{$page}}">{{$page}}</option>
                    @endfor
            </select>
            <button class="btn btn-outline-dark btn-sm" style="margin-left: 5px;font-size: 5px;" id="nextPage"><i class="fa fa-arrow-circle-right"></i></button>
        </div>
    </div>
    <div class="col-9">
        <div style="height: 803px; overflow-y: auto; border: 1px solid; display: grid; place-items: center; position: relative;" id="div-canvas">
            <div style="position: relative;">
                <canvas id="pdf-render"></canvas>
                <canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas>
            </div>
        </div>
    </div>
    <input type="hidden" name="id" id="id">
    <input type="hidden" name="users_id" id="users_id">
    <input type="hidden" name="positionX" id="positionX">
    <input type="hidden" name="positionY" id="positionY">
</div>
@endsection

@extends($extends)