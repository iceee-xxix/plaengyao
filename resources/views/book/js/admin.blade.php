@section('script')
<?php $position = $itemParent; ?>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $('.btn-default').hide();
    var signature = '{{$signature}}';
    var selectPageTable = document.getElementById('page-select-card');
    var pageTotal = '{{$totalPages}}';
    var pageNumTalbe = 1;
    var permission = '{{$permission}}';

    var imgData = null;

    function pdf(url) {
        var pdfDoc = null,
            pageNum = 1,
            pageRendering = false,
            pageNumPending = null,
            scale = 1.5,
            pdfCanvas = document.getElementById('pdf-render'),
            pdfCtx = pdfCanvas.getContext('2d'),
            markCanvas = document.getElementById('mark-layer'),
            markCtx = markCanvas.getContext('2d'),
            selectPage = document.getElementById('page-select');

        var markCoordinates = null;

        document.getElementById('add-stamp').disabled = true;

        function renderPage(num) {
            pageRendering = true;

            pdfDoc.getPage(num).then(function(page) {
                let viewport = page.getViewport({
                    scale: scale
                });
                pdfCanvas.height = viewport.height;
                pdfCanvas.width = viewport.width;
                markCanvas.height = viewport.height;
                markCanvas.width = viewport.width;

                let renderContext = {
                    canvasContext: pdfCtx,
                    viewport: viewport
                };
                let renderTask = page.render(renderContext);

                renderTask.promise.then(function() {
                    pageRendering = false;
                    if (pageNumPending !== null) {
                        renderPage(pageNumPending);
                        pageNumPending = null;
                    }
                });
            });

            selectPage.value = num;
        }

        function queueRenderPage(num) {
            if (pageRendering) {
                pageNumPending = num;
            } else {
                renderPage(num);
            }
        }

        function onNextPage() {
            if (pageNum >= pdfDoc.numPages) {
                return;
            }
            pageNum++;
            queueRenderPage(pageNum);
        }

        function onPrevPage() {
            if (pageNum <= 1) {
                return;
            }
            pageNum--;
            queueRenderPage(pageNum);
        }

        selectPage.addEventListener('change', function() {
            let selectedPage = parseInt(this.value);
            if (selectedPage && selectedPage >= 1 && selectedPage <= pdfDoc.numPages) {
                pageNum = selectedPage;
                queueRenderPage(selectedPage);
            }
        });

        pdfjsLib.getDocument(url).promise.then(function(pdfDoc_) {
            pdfDoc = pdfDoc_;
            for (let i = 1; i <= pdfDoc.numPages; i++) {
                let option = document.createElement('option');
                option.value = i;
                option.textContent = i;
                selectPage.appendChild(option);
            }

            renderPage(pageNum);
            document.getElementById('add-stamp').disabled = false;
        });


        document.getElementById('next').addEventListener('click', onNextPage);
        document.getElementById('prev').addEventListener('click', onPrevPage);


        let markEventListener = null;
        $('#add-stamp').click(function(e) {
            e.preventDefault();
            removeMarkListener();
            document.getElementById('add-stamp').disabled = true;
            document.getElementById('save-stamp').disabled = false;

            markEventListener = function(e) {
                var markCanvas = document.getElementById('mark-layer');
                var markCtx = markCanvas.getContext('2d');
                var rect = markCanvas.getBoundingClientRect();
                var startX = (e.clientX - rect.left);
                var startY = (e.clientY - rect.top);

                var endX = startX + 213;
                var endY = startY + 115;

                markCoordinates = {
                    startX,
                    startY,
                    endX,
                    endY
                };
                drawMark(startX, startY, endX, endY);
                $('#positionX').val(startX);
                $('#positionY').val(startY);

                var text = '{{$position_name}}';
                if (text.length >= 30) {
                    dynamicX = 5;
                } else if (text.length >= 20) {
                    dynamicX = 10;
                } else if (text.length >= 15) {
                    dynamicX = 60;
                } else if (text.length >= 13) {
                    dynamicX = 75;
                } else if (text.length >= 10) {
                    dynamicX = 70;
                } else {
                    dynamicX = 80;
                }
                drawTextHeader('15px Sarabun', startX + dynamicX, startY + 25, text);
                drawTextHeader('12px Sarabun', startX + 8, startY + 55, 'รับที่..........................................................');
                drawTextHeader('12px Sarabun', startX + 8, startY + 80, 'วันที่.........เดือน......................พ.ศ.........');
                drawTextHeader('12px Sarabun', startX + 8, startY + 100, 'เวลา......................................................น.');
            };

            var markCanvas = document.getElementById('mark-layer');
            markCanvas.addEventListener('click', markEventListener);
        });

        $('#modalForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $('#exampleModal').modal('hide');
            Swal.showLoading();
            $.ajax({
                type: "post",
                url: "/book/confirm_signature",
                data: formData,
                dataType: "json",
                contentType: false,
                processData: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.status) {
                        $('#exampleModal').modal('hide');
                        setTimeout(() => {
                            swal.close();
                        }, 1000);
                        resetMarking();
                        removeMarkListener();
                        document.getElementById('signature-save').disabled = false;

                        markEventListener = function(e) {
                            var markCanvas = document.getElementById('mark-layer');
                            var markCtx = markCanvas.getContext('2d');
                            var rect = markCanvas.getBoundingClientRect();
                            var startX = (e.clientX - rect.left);
                            var startY = (e.clientY - rect.top);

                            var endX = startX + 213;
                            var endY = startY + 115;

                            markCoordinates = {
                                startX,
                                startY,
                                endX,
                                endY
                            };
                            $('#positionX').val(startX);
                            $('#positionY').val(startY);

                            var text = $('#modal-text').val();
                            var lineBreakCount = countLineBreaks(text);
                            var checkedValues = $('input[type="checkbox"]:checked').map(function() {
                                return $(this).val();
                            }).get();
                            drawMarkSignature(startX - 40, startY + (20 * lineBreakCount), endX, endY, checkedValues);
                            drawTextHeaderSignature('15px Sarabun', startX, startY, text);

                            var i = 0;
                            var checkbox_text = '';
                            var checkbox_x = 0;
                            var plus_y = 20;
                            checkedValues.forEach(element => {
                                if (element == 4) {
                                    plus_y = 160;
                                }
                            });

                            checkedValues.forEach(element => {
                                switch (element) {
                                    case '1':
                                        checkbox_text = '({{$users->fullname}})';
                                        break;
                                    case '2':
                                        checkbox_text = '{{$permission_data->permission_name}}';
                                        break;
                                    case '3':
                                        checkbox_text = '{{convertDateToThai(date("Y-m-d"))}}';
                                        break;
                                }
                                if (element != 4) {
                                    drawTextHeaderSignature('15px Sarabun', startX, (startY + plus_y + (20 * lineBreakCount)) + (20 * i), checkbox_text);
                                }
                                i++;
                            });
                        };

                        var markCanvas = document.getElementById('mark-layer');
                        markCanvas.addEventListener('click', markEventListener);
                    } else {
                        $('#exampleModal').modal('hide');
                        Swal.fire("", response.message, "error");
                    }
                }
            });
        });

        function countLineBreaks(text) {
            var lines = text.split('\n');
            return lines.length - 1;
        }
        // ฟังก์ชันในการวาดกากบาทเล็กๆ ที่มุมขวาบน
        function drawMark(startX, startY, endX, endY) {
            var markCanvas = document.getElementById('mark-layer');
            var markCtx = markCanvas.getContext('2d');
            markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);

            markCtx.beginPath();
            markCtx.rect(startX, startY, endX - startX, endY - startY);
            markCtx.lineWidth = 1;
            markCtx.strokeStyle = 'blue';
            markCtx.stroke();

            var crossSize = 10;
            markCtx.beginPath();
            markCtx.moveTo(endX - crossSize, startY + crossSize);
            markCtx.lineTo(endX, startY);
            markCtx.moveTo(endX, startY + crossSize);
            markCtx.lineTo(endX - crossSize, startY);
            markCtx.lineWidth = 2;
            markCtx.strokeStyle = 'red';
            markCtx.stroke();

            markCanvas.addEventListener('click', function(event) {
                var rect = markCanvas.getBoundingClientRect();
                var clickX = event.clientX - rect.left;
                var clickY = event.clientY - rect.top;

                if (
                    clickX >= endX - crossSize && clickX <= endX &&
                    clickY >= startY && clickY <= startY + crossSize
                ) {
                    removeMarkListener();
                    var markCtx = markCanvas.getContext('2d');
                    markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);
                }
            });
        }

        function drawMarkSignature(startX, startY, endX, endY, checkedValues) {
            var markCanvas = document.getElementById('mark-layer');
            var markCtx = markCanvas.getContext('2d');
            markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);

            checkedValues.forEach(element => {
                if (element == 4) {
                    var img = new Image();
                    img.src = signature;
                    img.onload = function() {
                        markCtx.drawImage(img, startX, startY, 240, 130);
                        imgData = {
                            x: startX,
                            y: startY,
                            width: 150,
                            height: 100
                        };
                    }
                }
            });

        }

        function drawTextHeader(type, startX, startY, text) {
            var markCanvas = document.getElementById('mark-layer');
            var markCtx = markCanvas.getContext('2d');

            markCtx.font = type;
            markCtx.fillStyle = "blue";
            markCtx.fillText(text, startX, startY);
        }

        function drawTextHeaderSignature(type, startX, startY, text) {
            var markCanvas = document.getElementById('mark-layer');
            var markCtx = markCanvas.getContext('2d');

            markCtx.font = type;
            markCtx.fillStyle = "blue";

            var lines = text.split('\n');
            var lineHeight = 20;
            for (var i = 0; i < lines.length; i++) {
                markCtx.fillText(lines[i], startX, startY + (i * lineHeight));
            }
        }
    }

    let markEventListener = null;

    function openPdf(url, id, status, type, is_check = '', number_id, position_id) {
        $('.btn-default').hide();
        document.getElementById('add-stamp').disabled = false;
        document.getElementById('save-stamp').disabled = true;
        document.getElementById('send-save').disabled = true;
        $('#div-canvas').html('<div style="position: relative;"><canvas id="pdf-render"></canvas><canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas></div>');
        pdf(url);
        $('#id').val(id);
        $('#positionX').val('');
        $('#positionY').val('');
        $('#txt_label').text('');
        $('#users_id').val('');
        document.getElementById('add-stamp').disabled = true;
        if (status == 3) {
            $('#add-stamp').show();
            $('#save-stamp').show();
        }
        if (status == 3.5) {
            if (position_id != 1) {
                document.getElementById('send-signature').disabled = false;
                $('#send-signature').show();
                $('#signature-save').show();
            } else {
                $('#sendTo').show();
            }
        }
        if (status == 4) {
            if (permission != '3,3.5,4,5,14') {
                document.getElementById('send-signature').disabled = false;
                $('#send-signature').show();
                $('#signature-save').show();
            } else {
                $('#send-to').show();
                $('#send-save').show();
            }
        }
        if (status == 5) {
            $('#send-to').show();
            $('#send-save').show();
        }
        resetMarking();
        removeMarkListener();
    }

    function resetMarking() {
        var markCanvas = document.getElementById('mark-layer');
        var markCtx = markCanvas.getContext('2d');
        markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);
    }

    function removeMarkListener() {
        var markCanvas = document.getElementById('mark-layer');
        if (markEventListener) {
            markCanvas.removeEventListener('click', markEventListener);
            markEventListener = null;
        }
    }

    function resetMarking() {
        var markCanvas = document.getElementById('mark-layer');
        var markCtx = markCanvas.getContext('2d');
        markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height);
    }

    selectPageTable.addEventListener('change', function() {
        let selectedPage = parseInt(this.value);
        ajaxTable(selectedPage);
    });

    function onNextPageTable() {
        if (pageNumTalbe >= pageTotal) {
            return;
        }
        pageNumTalbe++;
        selectPageTable.value = pageNumTalbe;
        ajaxTable(pageNumTalbe);
    }

    function onPrevPageTable() {
        if (pageNumTalbe <= 1) {
            return;
        }
        pageNumTalbe--;
        selectPageTable.value = pageNumTalbe;
        ajaxTable(pageNumTalbe);
    }
    document.getElementById('nextPage').addEventListener('click', onNextPageTable);
    document.getElementById('prevPage').addEventListener('click', onPrevPageTable);

    function ajaxTable(pages) {
        $('#id').val('');
        $('#positionX').val('');
        $('#positionY').val('');
        $('#txt_label').text('');
        $('#users_id').val('');
        document.getElementById('add-stamp').disabled = false;
        document.getElementById('save-stamp').disabled = true;
        document.getElementById('send-save').disabled = true;
        $.ajax({
            type: "post",
            url: "/book/dataList",
            data: {
                pages: pages,
            },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            dataType: "json",
            success: function(response) {
                if (response.status == true) {
                    $('#box-card-item').empty();
                    $('#div-canvas').html('<div style="position: relative;"><canvas id="pdf-render"></canvas><canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas></div>');
                    response.book.forEach(element => {
                        $html = '<a href="javascript:void(0)" onclick="openPdf(' + "'" + element.url + "'" + ',' + "'" + element.id + "'" + ',' + "'" + element.status + "'" + ')"><div class="card border-dark mb-2"><div class="card-header text-dark fw-bold">' + element.inputSubject + '</div><div class="card-body text-dark"><div class="row"><div class="col-9">' + element.selectBookFrom + '</div><div class="col-3 fw-bold">' + element.showTime + ' น.</div></div></div></div></a>';
                        $('#box-card-item').append($html);
                    });
                }
            }
        });
    }

    $('#search_btn').click(function(e) {
        e.preventDefault();
        $('#id').val('');
        $('#positionX').val('');
        $('#positionY').val('');
        $('.btn-default').hide();
        $('#txt_label').text('');
        $('#users_id').val('');
        document.getElementById('add-stamp').disabled = false;
        document.getElementById('save-stamp').disabled = true;
        document.getElementById('send-save').disabled = true;
        $.ajax({
            type: "post",
            url: "/book/dataListSearch",
            data: {
                pages: 1,
                search: $('#inputSearch').val()
            },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            dataType: "json",
            success: function(response) {
                if (response.status == true) {
                    $('#box-card-item').html('');
                    $('#div-canvas').html('<div style="position: relative;"><canvas id="pdf-render"></canvas><canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas></div>');
                    pageNumTalbe = 1;
                    pageTotal = response.totalPages;
                    response.book.forEach(element => {
                        $html = '<a href="javascript:void(0)" onclick="openPdf(' + "'" + element.url + "'" + ',' + "'" + element.id + "'" + ',' + "'" + element.status + "'" + ')"><div class="card border-dark mb-2"><div class="card-header text-dark fw-bold">' + element.inputSubject + '</div><div class="card-body text-dark"><div class="row"><div class="col-9">' + element.selectBookFrom + '</div><div class="col-3 fw-bold">' + element.showTime + ' น.</div></div></div></div></a>';
                        $('#box-card-item').append($html);
                    });
                    $("#page-select-card").empty();
                    for (let index = 1; index <= pageTotal; index++) {
                        $('#page-select-card').append('<option value="' + index + '">' + index + '</option>');
                    }
                }
            }
        });
    });

    $('#save-stamp').click(function(e) {
        e.preventDefault();
        var id = $('#id').val();
        var positionX = $('#positionX').val();
        var positionY = $('#positionY').val();
        var pages = $('#page-select').find(":selected").val();
        if (id != '' && positionX != '' && positionY != '') {
            Swal.fire({
                title: "ยืนยันการลงบันทึกเวลา",
                showCancelButton: true,
                confirmButtonText: "ตกลง",
                cancelButtonText: `ยกเลิก`,
                icon: 'question'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "post",
                        url: "/book/admin_stamp",
                        data: {
                            id: id,
                            positionX: positionX,
                            positionY: positionY,
                            pages: pages
                        },
                        dataType: "json",
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.status) {
                                Swal.fire("", "บันทึกเรียบร้อย", "success");
                                setTimeout(() => {
                                    location.reload();
                                }, 1000);
                            } else {
                                Swal.fire("", "บันทึกไม่สำเร็จ", "error");
                            }
                        }
                    });
                }
            });
        } else {
            Swal.fire("", "กรุณาเลือกตำแหน่งของตราประทับ", "info");
        }
    });

    $('#sendTo').click(function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'เลือกหน่วยงานที่ต้องการแทงเรื่อง',
            html: `
                <select id="select_position_id" name="states[]" multiple="multiple" class="swal2-input" style="width: 80%;">
                    @foreach($position as $key => $rec)
                    <option value="{{$key}}">{{$rec}}</option>
                    @endforeach
                </select>
            `,
            didOpen: () => {
                $('#select_position_id').select2({
                    dropdownParent: $('.swal2-container')
                });
            },
            allowOutsideClick: false,
            focusConfirm: true,
            confirmButtonText: 'ตกลง',
            showCancelButton: true,
            cancelButtonText: `ยกเลิก`,
            preConfirm: () => {
                // ดึงค่าที่เลือกจาก Select2
                const selectedValue = $('#select_position_id').val();
                if (!selectedValue) {
                    Swal.showValidationMessage('ท่านยังไม่ได้เลือกหน่วยงาน');
                }
                return selectedValue;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                var id = $('#id').val();
                $.ajax({
                    type: "post",
                    url: "/book/send_to_adminParent",
                    data: {
                        id: id,
                        position_id: result.value
                    },
                    dataType: "json",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.status) {
                            if (response.status) {
                                Swal.fire("", "แทงเรื่องเรียบร้อยแล้ว", "success");
                                setTimeout(() => {
                                    location.reload();
                                }, 1000);
                            } else {
                                Swal.fire("", "แทงเรื่องไม่สำเร็จ", "error");
                            }
                        }
                    }
                });
            }
        });
    });
    $('#send-to').click(function(e) {
        e.preventDefault();
        $.ajax({
            type: "post",
            url: "/book/checkbox_send",
            dataType: "json",
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                Swal.fire({
                    title: 'แทงเรื่อง',
                    html: response,
                    allowOutsideClick: false,
                    focusConfirm: true,
                    confirmButtonText: 'ตกลง',
                    showCancelButton: true,
                    cancelButtonText: `ยกเลิก`,
                    preConfirm: () => {
                        var selectedCheckboxes = [];
                        var textCheckboxes = [];
                        $('input[name="flexCheckChecked[]"]:checked').each(function() {
                            selectedCheckboxes.push($(this).val());
                            textCheckboxes.push($(this).next('label').text().trim());
                        });

                        console.log(selectedCheckboxes);
                        if (selectedCheckboxes.length === 0) {
                            Swal.showValidationMessage('กรุณาเลือกตัวเลือก');
                        }

                        return {
                            id: selectedCheckboxes,
                            text: textCheckboxes
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        var id = '';
                        var txt = '- แทงเรื่อง ('
                        for (let index = 0; index < result.value.text.length; index++) {
                            if (index > 0 && index < result.value.text.length) {
                                txt += ',';
                            }
                            txt += result.value.text[index];
                        }
                        for (let index = 0; index < result.value.id.length; index++) {
                            if (index > 0 && index < result.value.id.length) {
                                id += ',';
                            }
                            id += result.value.id[index];
                        }
                        txt += ') -';
                        $('#txt_label').text(txt);
                        $('#users_id').val(id);
                        document.getElementById('send-save').disabled = false;
                    }
                });
            }
        });
    });

    $('#send-save').click(function(e) {
        e.preventDefault();
        var id = $('#id').val();
        var users_id = $('#users_id').val();
        Swal.fire({
            title: "ยืนยันการแทงเรื่อง",
            showCancelButton: true,
            confirmButtonText: "ตกลง",
            cancelButtonText: `ยกเลิก`,
            icon: 'question'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "post",
                    url: "/book/send_to_save",
                    data: {
                        id: id,
                        users_id: users_id,
                        status: 6
                    },
                    dataType: "json",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.status) {
                            if (response.status) {
                                Swal.fire("", "แทงเรื่องเรียบร้อยแล้ว", "success");
                                setTimeout(() => {
                                    location.reload();
                                }, 1000);
                            } else {
                                Swal.fire("", "แทงเรื่องไม่สำเร็จ", "error");
                            }
                        }
                    }
                });
            }
        });
    });
    $('#signature-save').click(function(e) {
        e.preventDefault();
        var id = $('#id').val();
        var positionX = $('#positionX').val();
        var positionY = $('#positionY').val();
        var pages = $('#page-select').find(":selected").val();
        var text = $('#modal-text').val();
        var checkedValues = $('input[type="checkbox"]:checked').map(function() {
            return $(this).val();
        }).get();
        if (id != '' && positionX != '' && positionY != '') {
            Swal.fire({
                title: "ยืนยันการลงเกษียณหนังสือ",
                showCancelButton: true,
                confirmButtonText: "ตกลง",
                cancelButtonText: `ยกเลิก`,
                icon: 'question'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "post",
                        url: "/book/signature_stamp",
                        data: {
                            id: id,
                            positionX: positionX,
                            positionY: positionY,
                            pages: pages,
                            text: text,
                            checkedValues: checkedValues
                        },
                        dataType: "json",
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.status) {
                                Swal.fire("", "ลงบันทึกเกษียณหนังสือเรียบร้อย", "success");
                                setTimeout(() => {
                                    location.reload();
                                }, 1000);
                            } else {
                                Swal.fire("", "บันทึกไม่สำเร็จ", "error");
                            }
                        }
                    });
                }
            });
        } else {
            Swal.fire("", "กรุณาเลือกตำแหน่งเกษียณหนังสือ", "info");
        }
    });
    $(document).ready(function() {
        $('#send-signature').click(function(e) {
            e.preventDefault();
            $('#exampleModal').modal('show');
        });
        $('#exampleModal').on('show.bs.modal', function(event) {
            $('input[type="password"]').val('');
            $('textarea').val('');
        });
    });
</script>
<div class="modal modal-lg fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="modalForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">เกษียณหนังสือ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 row">
                        <label for="inputPassword" class="col-sm-2 col-form-label"><span class="req">*</span>ข้อความเกษียน :</label>
                        <div class="col-sm-10">
                            <textarea rows="4" class="form-control" name="modal-text" id="modal-text"></textarea>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <div class="col-2">
                        </div>
                        <div class="col-sm-10 d-flex justify-content-center text-center">
                            ({{$users->fullname}})<br>
                            {{$permission_data->permission_name}}<br>
                            {{convertDateToThai(date("Y-m-d"))}}
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="inputPassword" class="col-sm-2 col-form-label"><span class="req">*</span>รหัสผ่านเกษียน :</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" id="modal-Password" name="modal-Password">
                        </div>
                    </div>
                    <div class="row">
                        <label for="inputPassword" class="col-sm-2 col-form-label"><span class="req">*</span>แสดงผล :</label>
                        <div class="col-sm-10 d-flex align-items-center">
                            <ul class="list-group list-group-horizontal">
                                <li class="list-group-item"><input class="form-check-input me-1" type="checkbox" name="modal-check[]" value="1" checked>ชื่อ-นามสกุล</li>
                                <li class="list-group-item"><input class="form-check-input me-1" type="checkbox" name="modal-check[]" value="2" checked>ตำแหน่ง</li>
                                <li class="list-group-item"><input class="form-check-input me-1" type="checkbox" name="modal-check[]" value="3" checked>วันที่</li>
                                <li class="list-group-item"><input class="form-check-input me-1" type="checkbox" name="modal-check[]" value="4" checked>ลายเซ็น</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" id="submit-modal" class="btn btn-primary">ตกลง</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection