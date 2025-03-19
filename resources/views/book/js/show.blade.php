@section('script')
<?php $position = [1 => 'สำนักงานปลัด', 2 => 'งานกิจการสภา', 3 => 'กองคลัง', 4 => 'กองช่าง', 5 => 'กองการศึกษา ศาสนาและวัฒนธรรม', 6 => 'ฝ่ายศูนย์รับเรื่องร้องเรียน-ร้องทุกข์', 7 => 'ฝ่ายเลือกตั้ง', 8 => 'ฝ่ายสปสช.', 9 => 'ศูนย์ข้อมูลข่าวสาร']; ?>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    var permission_id = '{{$permission_id}}';
    var selectPageTable = document.getElementById('page-select-card');
    var pageTotal = '{{$totalPages}}';
    var pageNumTalbe = 1;


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

                drawTextHeader('15px Sarabun', startX + 3, startY + 25, 'องค์การบริหารส่วนตำบลแปลงยาว');
                drawTextHeader('12px Sarabun', startX + 8, startY + 55, 'รับที่..........................................................');
                drawTextHeader('12px Sarabun', startX + 8, startY + 80, 'วันที่.........เดือน......................พ.ศ.........');
                drawTextHeader('12px Sarabun', startX + 8, startY + 100, 'เวลา......................................................น.');
            };

            var markCanvas = document.getElementById('mark-layer');
            markCanvas.addEventListener('click', markEventListener);
        });

        function removeMarkListener() {
            var markCanvas = document.getElementById('mark-layer');
            if (markEventListener) {
                markEventListener = null;
            }
            $('#positionX').val('');
            $('#positionY').val('');
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
                    markCtx.clearRect(0, 0, markCanvas.width, markCanvas.height); // เคลียร์แคนวาส
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
    }

    let markEventListener = null;

    function openPdf(url, id, status) {
        $('.btn-default').hide();
        document.getElementById('add-stamp').disabled = false;
        document.getElementById('save-stamp').disabled = true;
        $('#div-canvas').html('<div style="position: relative;"><canvas id="pdf-render"></canvas><canvas id="mark-layer" style="position: absolute; left: 0; top: 0;"></canvas></div>');
        pdf(url);
        $('#id').val(id);
        $('#positionX').val('');
        $('#positionY').val('');
        document.getElementById('add-stamp').disabled = true;
        // if (permission_id != '1') {
            if (status == 1) {
                $('#add-stamp').show();
                $('#save-stamp').show();
            }
            if (status == 2) {
                $('#send-to').show();
            }
        // }
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
        document.getElementById('add-stamp').disabled = false;
        document.getElementById('save-stamp').disabled = true;
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
                        $html = '<a href="javascript:void(0)" onclick="openPdf(' + "'" + element.url + "'" + ',' + "'" + element.id + "'" + ',' + "'" + element.status + "'" + ')"><div class="card border-dark mb-2"><div class="card-header text-dark fw-bold">' + element.inputSubject + '</div><div class="card-body text-dark"><div class="row"><div class="col-9">' + element.fullname + '</div><div class="col-3 fw-bold">' + element.showTime + ' น.</div></div></div></div></a>';
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
        document.getElementById('add-stamp').disabled = false;
        document.getElementById('save-stamp').disabled = true;
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
                        $html = '<a href="javascript:void(0)" onclick="openPdf(' + "'" + element.url + "'" + ',' + "'" + element.id + "'" + ',' + "'" + element.status + "'" + ')"><div class="card border-dark mb-2"><div class="card-header text-dark fw-bold">' + element.inputSubject + '</div><div class="card-body text-dark"><div class="row"><div class="col-9">' + element.fullname + '</div><div class="col-3 fw-bold">' + element.showTime + ' น.</div></div></div></div></a>';
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
                        url: "/book/save_stamp",
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

    $('#send-to').click(function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'เลือกหน่วยงานที่ต้องการแทงเรื่อง',
            html: `
                <select id="position_id" name="states[]" multiple="multiple" class="swal2-input" style="width: 80%;">
                    @foreach($position as $key => $rec)
                    <option value="{{$key}}">{{$rec}}</option>
                    @endforeach
                </select>
            `,
            didOpen: () => {
                $('#position_id').select2({
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
                const selectedValue = $('#position_id').val();
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
                    url: "/book/send_to_admin",
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
</script>

@endsection