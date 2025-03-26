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

    .hidden {
        display: none;
    }

    span.req {
        color: red;
    }

    #upload-area {
        border: 2px dashed #ccc;
        padding: 10px;
        text-align: center;
        background-color: #fff;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);

        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
    }

    #upload-area.dragover {
        border-color: #007bff;
        background-color: #f8f9fa;
    }

    .upload-icon {
        width: 80px;
        height: auto;
        margin-bottom: 20px;
    }

    #pdf-container {
        background-color: #fff;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        margin: 0px auto;
        overflow-y: auto;
        width: 100%;
    }

    #pdf-container canvas {
        margin-bottom: 20px;
        border: 1px solid #ccc;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
                        <button class="btn btn-outline-dark btn-sm hidden btn-default" style="margin-right: 5px;font-size: 15px;" id="number-stamp" title="ประทับเลขที่รับ">ประทับเลขที่รับ</button>
                        <button class="btn btn-outline-dark btn-sm hidden btn-default" style="margin-right: 5px;font-size: 15px;" id="number-save" title="บันทึก" disabled><i class="fa fa-floppy-o"></i></button>
                        <button class="btn btn-outline-dark btn-sm hidden btn-default" style="margin-right: 5px;font-size: 15px;" id="save-stamp" title="บันทึก" disabled><i class="fa fa-floppy-o"></i></button>
                        <button class="btn btn-outline-dark btn-sm hidden btn-default" style="margin-right: 5px;font-size: 15px;" id="save-pdf" title="บันทึก" disabled><i class="fa fa-floppy-o"></i></button>
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
            @foreach ($book as $rec)
            <?php
            $color = 'info';
            if ($rec->type != 1) {
                $color = 'warning';
            }
            if ($rec->file) {
                $action = "openPdf('" . $rec->url . "','" . $rec->id . "','" . $rec->status . "','" . $rec->type . "','" . $rec->is_number_stamp . "','" . $rec->inputBookregistNumber . "','" . $rec->position_id . "')";
            } else {
                $action = "uploadPdf('" . $rec->id . "')";
            }
            ?>
            <a href="javascript:void(0)" onclick="{{$action}}">
                <div class="card border-{{$color}} mb-2">
                    <div class="card-header text-dark fw-bold">{{$rec->inputSubject}}</div>
                    <div class="card-body text-dark">
                        <div class="row">
                            <div class="col-9">{{ $rec->selectBookFrom }}</div>
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
    <div class="col-9" id="div-showPdf">
        <div style="height: 803px; overflow-y: auto; border: 1px solid; display: grid; place-items: center; position: relative;" id="div-canvas">
            <div style="position: relative;">
                <canvas id="pdf-render"></canvas>
                <canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-9 hidden" id="div-uploadPdf">
        <div class="col-12" id="uploadDiv">
            <div style="height: 803px; overflow-y: auto;display: grid;position: relative;">
                <div id="upload-area">
                    <div class="upload-container">
                        <img src="{{url('/template/icon/upload.png')}}" alt="Cloud Upload Icon" class="upload-icon">
                        <input type="file" id="file-input" name="file-input" style="opacity: 0; position: absolute;" accept="application/pdf">
                        <p>DRAG & DROP FILE HERE OR</p>
                        <button type="button" id="browse-btn" class="btn btn-outline-info">Browse files</button>
                    </div>
                </div>
                <div id="pdf-container" class="hidden" style="overflow-y: scroll;"></div>
            </div>
        </div>
    </div>
    <input type="hidden" name="id" id="id">
    <input type="hidden" name="position_id" id="position_id">
    <input type="hidden" name="number_id" id="number_id">
    <input type="hidden" name="users_id" id="users_id">
    <input type="hidden" name="positionX" id="positionX">
    <input type="hidden" name="positionY" id="positionY">
</div>
@endsection

@extends($extends)