<nav class="pc-sidebar pc-trigger pc-sidebar-hide"></nav>
<header class="pc-header">
    <div class="header-wrapper d-flex justify-content-between align-items-center">
        <div class="me-auto pc-mob-drp">
            <ul class="list-unstyled d-flex align-items-center mb-0">
                <a href="{{url('book')}}" type="button" class="btn btn-outline-primary {{($function_key == 'index') ? 'active' : ''}}" style="margin-right:10px">นำเข้าหนังสือ</a>
                <a href="{{url('book/show')}}" type="button" class="btn btn-outline-primary {{($function_key == 'show') ? 'active' : ''}}" style="margin-right:10px">รายการหนังสือ</a>
            </ul>
        </div>
        <div class="ms-auto">
            <ul class="list-unstyled">
                <li class="dropdown pc-h-item header-user-profile">
                    <a class="pc-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#" role="button"
                        aria-haspopup="false" data-bs-auto-close="outside" aria-expanded="false">
                        <img src="http://localhost/project/dist/assets/images/user/avatar-2.jpg" alt="user-image" class="user-avtar" style="width:35px">
                    </a>
                    <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown">
                        <div class="dropdown-header">
                            <div class="d-flex mb-1">
                                <div class="flex-shrink-0">
                                    <img src="http://localhost/project/dist/assets/images/user/avatar-2.jpg" alt="user-image" class="user-avtar wid-35">
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1"><?= session()->get('fullname') ?></h6>
                                    <span><?= session()->get('permission_name') ?>แผนก<?= session()->get('position_name') ?></span>
                                </div>
                                <a href="{{url('/login/logout')}}" class=" pc-head-link bg-transparent"><i class="fa fa-sign-out"></i></a>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</header>