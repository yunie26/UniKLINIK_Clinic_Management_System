<?php

function createHeader($icon, $heading, $sub_heading) {

    $admin_user = isset($_SESSION['admin'])
        ? $_SESSION['admin']
        : 'Administrator';

    echo '

<!-- FONT AWESOME -->
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

<style>

.dashboard-header{
    background:#ffffff;
    border-radius:24px;
    padding:24px 30px;
    margin-bottom:28px;
    box-shadow:0 10px 35px rgba(15,23,42,0.06);
    border:1px solid #edf2f7;
}

.header-wrapper{
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:20px;
}

/* LEFT */

.header-left{
    display:flex;
    align-items:center;
    gap:18px;
}

.header-icon{
    width:62px;
    height:62px;
    border-radius:20px;
    background:linear-gradient(135deg,#0ea5e9,#0284c7);
    display:flex;
    justify-content:center;
    align-items:center;
    color:white;
    font-size:24px;
    box-shadow:0 6px 18px rgba(14,165,233,0.25);
}

.header-text h3{
    margin:0;
    font-size:28px;
    font-weight:700;
    color:#0f172a;
}

.header-text p{
    margin:5px 0 0;
    font-size:14px;
    color:#64748b;
}

/* RIGHT */

.header-right{
    display:flex;
    align-items:center;
    gap:14px;
    position:relative;
}

.header-btn{
    width:48px;
    height:48px;
    border:none;
    border-radius:16px;
    background:#f8fafc;
    color:#334155;
    font-size:18px;
    cursor:pointer;
    transition:0.25s ease;
    position:relative;
}

.header-btn:hover{
    background:#0ea5e9;
    color:white;
    transform:translateY(-2px);
}

.notification-badge{
    position:absolute;
    top:-4px;
    right:-4px;
    background:#ef4444;
    color:white;
    min-width:18px;
    height:18px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:10px;
    font-weight:700;
}

/* PROFILE */

.admin-profile{
    display:flex;
    align-items:center;
    gap:12px;
    background:#f8fafc;
    padding:8px 14px;
    border-radius:16px;
}

.admin-avatar{
    width:42px;
    height:42px;
    border-radius:50%;
    background:linear-gradient(135deg,#06b6d4,#0891b2);
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-size:15px;
    font-weight:700;
}

.admin-info span{
    display:block;
    font-size:12px;
    color:#64748b;
}

.admin-info strong{
    color:#0f172a;
    font-size:14px;
}

/* DROPDOWN */

.notification-panel,
.options{
    position:absolute;
    top:68px;
    right:0;
    background:white;
    border-radius:18px;
    border:1px solid #e2e8f0;
    box-shadow:0 18px 45px rgba(0,0,0,0.08);
    overflow:hidden;
    z-index:9999;
}

.notification-panel{
    width:320px;
}

.options{
    width:240px;
    list-style:none;
    padding:0;
    margin:0;
}

.notification-header{
    padding:16px 18px;
    background:#f8fafc;
    font-weight:700;
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
    border-top:1px solid #e2e8f0;
    background:#f8fafc;
}

.notification-footer a,
.options li a{
    display:flex;
    align-items:center;
    gap:10px;
    padding:15px 18px;
    text-decoration:none;
    color:#334155;
    transition:0.2s ease;
}

.notification-footer a:hover,
.options li a:hover{
    background:#f1f5f9;
    color:#0ea5e9;
}

/* MOBILE */

@media(max-width:768px){

    .header-wrapper{
        flex-direction:column;
        align-items:flex-start;
    }

    .header-right{
        width:100%;
        justify-content:flex-end;
    }

}

</style>

<section class="dashboard-header">

    <div class="header-wrapper">

        <div class="header-left">

            <div class="header-icon">
                <i class="fa fa-'.$icon.'"></i>
            </div>

            <div class="header-text">
                <h3>'.$heading.'</h3>
                <p>'.$sub_heading.'</p>
            </div>

        </div>

        <div class="header-right">

            <!-- NOTIFICATION -->

            <button class="header-btn"
                    onclick="toggleNotifications(event)">

                <i class="fa fa-bell"></i>

                <span id="notification_count"
                      class="notification-badge"
                      style="display:none;">

                    0

                </span>

            </button>

            <ul id="notification_panel"
                class="notification-panel"
                style="display:none;">

                <li class="notification-header">
                    Notifications
                </li>

                <li id="notification_list_container">
                    <div id="notification_list"></div>
                </li>

                <li class="notification-footer">

                    <a href="all_notifications.php">

                        <i class="fa fa-eye"></i>

                        View All Notifications

                    </a>

                </li>

            </ul>

            <!-- SETTINGS -->

            <button class="header-btn"
                    onclick="showOptions(event)">

                <i class="fa fa-cog"></i>

            </button>

            <!-- PROFILE -->

            <div class="admin-profile">

                <div class="admin-avatar">
                    '.strtoupper(substr($admin_user,0,1)).'
                </div>

                <div class="admin-info">

                    <span>Administrator</span>

                    <strong>'.$admin_user.'</strong>

                </div>

            </div>

            <!-- OPTIONS -->

            <ul id="options"
                class="options"
                style="display:none;">

                <li>

                    <a href="my_profile.php">

                        <i class="fa fa-user-circle"></i>

                        My Profile

                    </a>

                </li>

                <li>

                    <a href="change_password.php">

                        <i class="fa fa-lock"></i>

                        Change Password

                    </a>

                </li>

                <li>

                    <a href="logout.php">

                        <i class="fa fa-sign-out"></i>

                        Logout

                    </a>

                </li>

            </ul>

        </div>

    </div>

</section>

<script src="js/notifications.js"></script>

<script>

if(typeof initNotifications === "function"){
    initNotifications("admin", "'.$admin_user.'");
}

function toggleNotifications(event){

    event.stopPropagation();

    var panel =
        document.getElementById("notification_panel");

    var options =
        document.getElementById("options");

    if(panel.style.display === "block"){

        panel.style.display = "none";

    }
    else{

        panel.style.display = "block";

        options.style.display = "none";

    }

}

function showOptions(event){

    event.stopPropagation();

    var options =
        document.getElementById("options");

    var panel =
        document.getElementById("notification_panel");

    if(options.style.display === "block"){

        options.style.display = "none";

    }
    else{

        options.style.display = "block";

        panel.style.display = "none";

    }

}

document.addEventListener("click", function(){

    document.getElementById("notification_panel")
        .style.display = "none";

    document.getElementById("options")
        .style.display = "none";

});

</script>

';

}

?>