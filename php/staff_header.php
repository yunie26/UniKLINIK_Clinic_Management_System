<?php

function createHeader($icon, $heading, $sub_heading) {

    $staff_id = isset($_SESSION['staff_id'])
        ? $_SESSION['staff_id']
        : 'staff';

    $staff_role = isset($_SESSION['staff_role'])
        ? $_SESSION['staff_role']
        : 'Staff';

    echo '

<style>

.content-header{
    background:#ffffff;
    border-radius:22px;
    padding:22px 28px;
    margin-bottom:25px;
    box-shadow:0 8px 30px rgba(15,23,42,0.06);
    border:1px solid #eaf0f6;
    align-items:center;
    position:relative;
    z-index:100;
}

/* TITLE */

.header-title{
    display:flex;
    flex-direction:column;
    justify-content:center;
}

.header-main{
    font-size:28px;
    font-weight:700;
    color:#0f172a;
    margin-bottom:6px;
}

.header-main i{
    color:#0ea5e9;
    margin-right:12px;
}

.header-sub{
    font-size:14px;
    color:#64748b;
    font-weight:500;
}

.role-badge{
    background:#e0f2fe;
    color:#0284c7;
    padding:5px 14px;
    border-radius:30px;
    font-size:12px;
    font-weight:700;
    margin-left:8px;
}

/* USER OPTIONS */

.user_options{
    display:flex;
    justify-content:flex-end;
    align-items:center;
    gap:14px;
    position:relative;
}

/* ICON BUTTON */

.icon-btn{
    width:46px;
    height:46px;
    border:none;
    border-radius:14px;
    background:#f8fafc;
    color:#334155;
    transition:0.25s ease;
    position:relative;
    outline:none !important;
    cursor:pointer;
}

.icon-btn:hover{
    background:#0ea5e9;
    transform:translateY(-2px);
}

.icon-btn:hover i{
    color:white;
}

/* SETTINGS ICON FIX */

.settings-btn i{
    font-size:18px;
    color:#334155 !important;
}

.settings-btn:hover i{
    color:white !important;
}

/* NOTIFICATION */

.notification_wrapper{
    position:relative;
}

.notification-badge{
    position:absolute;
    top:8px;
    right:8px;
    background:#ef4444;
    color:white;
    border-radius:50%;
    min-width:18px;
    height:18px;
    font-size:11px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
}

/* DROPDOWNS */

.notification-panel,
.options{
    position:absolute;
    right:0;
    top:60px;
    background:white;
    border-radius:18px;
    box-shadow:0 15px 40px rgba(0,0,0,0.08);
    border:1px solid #e2e8f0;
    overflow:hidden;
    z-index:9999;
    animation:fadeIn 0.2s ease;
}

.notification-panel{
    width:320px;
}

.options{
    width:240px;
}

@keyframes fadeIn{
    from{
        opacity:0;
        transform:translateY(10px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

.notification-header{
    padding:16px;
    font-weight:700;
    background:#f8fafc;
    color:#0f172a;
    border-bottom:1px solid #e2e8f0;
}

#notification_panel{
    list-style:none;
    padding:0;
    margin:0;
}

#notification_list_container{
    list-style:none;
    padding:0;
    margin:0;
}

#notification_list{
    max-height:380px;
    overflow-y:auto;
}

.notification-item{
    list-style:none;
    border-bottom:1px solid #f1f5f9;
}

.notification-item:last-child{
    border-bottom:none;
}

.notification-item a{
    display:block;
    padding:14px 18px;
    text-decoration:none;
    color:#334155;
    transition:0.2s ease;
}

.notification-item a:hover{
    background:#f8fafc;
    text-decoration:none;
    color:#0f172a;
}

.notification-title{
    font-weight:600;
    color:#0f172a;
    font-size:14px;
    margin-bottom:2px;
}

.notification-message{
    color:#475569;
    font-size:13px;
    line-height:1.45;
    margin-bottom:4px;
}

.notification-time{
    display:block;
    color:#94a3b8;
    font-size:11px;
}

.notification-empty{
    list-style:none;
    padding:24px 18px;
    text-align:center;
    color:#94a3b8;
    font-size:13px;
}

.notification-footer{
    padding:14px;
    background:#f8fafc;
    border-top:1px solid #e2e8f0;
}

.notification-footer a{
    color:#0ea5e9;
    font-weight:600;
    text-decoration:none;
}

.options{
    list-style:none;
    padding:0;
    margin:0;
}

.options li{
    list-style:none;
}

.options li a{
    display:flex;
    align-items:center;
    gap:12px;
    padding:16px 18px;
    color:#334155;
    text-decoration:none;
    transition:0.2s ease;
    font-weight:600;
}

.options li a:hover{
    background:#f8fafc;
    color:#0ea5e9;
    text-decoration:none;
}

.options li a i{
    width:18px;
}

/* MOBILE */

@media(max-width:768px){

    .content-header{
        padding:20px;
    }

    .header-main{
        font-size:22px;
    }

    .user_options{
        margin-top:20px;
        justify-content:flex-start;
    }

}

</style>

<section class="row content-header">

    <div class="col-md-9 header-title">

        <div class="header-main">
            <i class="fa fa-'.$icon.'"></i>
            '.$heading.'
        </div>

        <div class="header-sub">

            '.$sub_heading.'

            <span class="role-badge">
                '.$staff_role.'
            </span>

        </div>

    </div>

    <div class="col-md-3 user_options">

        <!-- NOTIFICATION -->

        <div class="notification_wrapper">

            <button type="button"
                    class="icon-btn"
                    id="notification_btn">

                <i class="fa fa-bell"></i>

                <span id="notification_count"
                      class="notification-badge"
                      style="display:none;">

                    0

                </span>

            </button>

            <ul id="notification_panel"
                class="notification-panel list-unstyled"
                style="display:none;">

                <li class="notification-header">
                    Notifications
                </li>

                <li id="notification_list_container">
                    <div id="notification_list"></div>
                </li>

                <li class="notification-footer text-center">

                    <a href="all_notifications.php">
                        View all notifications
                    </a>

                </li>

            </ul>

        </div>

        <!-- SETTINGS -->

        <div style="position:relative;">

            <button type="button"
                    class="icon-btn settings-btn"
                    id="settings_btn">

                <i class="fa fa-cog"></i>

            </button>

            <ul id="options"
                class="options list-unstyled"
                style="display:none;">

                <li>

                    <a href="staff_profile.php">

                        <i class="fa fa-user"></i>

                        <span>My Profile</span>

                    </a>

                </li>

                <li>

                    <a href="logout.php">

                        <i class="fa fa-sign-out"></i>

                        <span>Logout</span>

                    </a>

                </li>

            </ul>

        </div>

    </div>

</section>

<script src="js/notifications.js"></script>

<script>

document.addEventListener("DOMContentLoaded", function(){

    // =========================
    // INIT NOTIFICATION
    // =========================

    if(typeof initNotifications === "function"){

        initNotifications("staff", "'.$staff_id.'");

    }

    // =========================
    // GET ELEMENTS
    // =========================

    const notificationBtn =
        document.getElementById("notification_btn");

    const settingsBtn =
        document.getElementById("settings_btn");

    const notificationPanel =
        document.getElementById("notification_panel");

    const optionsPanel =
        document.getElementById("options");

    // =========================
    // NOTIFICATION TOGGLE
    // =========================

    notificationBtn.addEventListener("click", function(event){

        event.stopPropagation();

        if(notificationPanel.style.display === "block"){

            notificationPanel.style.display = "none";

        }
        else{

            notificationPanel.style.display = "block";

            optionsPanel.style.display = "none";

        }

    });

    // =========================
    // SETTINGS TOGGLE
    // =========================

    settingsBtn.addEventListener("click", function(event){

        event.stopPropagation();

        if(optionsPanel.style.display === "block"){

            optionsPanel.style.display = "none";

        }
        else{

            optionsPanel.style.display = "block";

            notificationPanel.style.display = "none";

        }

    });

    // =========================
    // PREVENT CLOSE INSIDE PANEL
    // =========================

    notificationPanel.addEventListener("click", function(event){

        event.stopPropagation();

    });

    optionsPanel.addEventListener("click", function(event){

        event.stopPropagation();

    });

    // =========================
    // CLOSE OUTSIDE CLICK
    // =========================

    document.addEventListener("click", function(){

        notificationPanel.style.display = "none";

        optionsPanel.style.display = "none";

    });

});

</script>

';
}

?>