<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0, user-scalable=no">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($conf_site_title) ?>">
<meta name="mobile-web-app-capable" content="yes">
<meta name="theme-color" content="#101223">
<title>登录 - <?= htmlspecialchars($conf_site_title) ?></title>
<meta name="referrer" content="no-referrer">
<link rel="apple-touch-icon" href="https://pub-8a73d0f678a64dc9abb02ea54e4c44c5.r2.dev/logo.png">
<link rel="icon" type="image/png" href="https://pub-8a73d0f678a64dc9abb02ea54e4c44c5.r2.dev/logo.png">
<link rel="icon" href="https://pub-8a73d0f678a64dc9abb02ea54e4c44c5.r2.dev/logo.png">
<style>

/* 登录页视觉统一到 install.php 的玻璃拟态 UI；仅调整样式，不改登录流程 */
:root {
    --ay-accent-start: #ff7dc6;
    --ay-accent-end: #7aa8ff;
    --ay-text: #FFFFFF;
    --ay-sub: #F0F2F7;
    --ay-card: rgba(12, 14, 28, .15);
    --ay-stroke: rgba(255, 255, 255, .22);
    --ay-input: rgba(255, 255, 255, .09);
    --ay-input-h: 48px;
    --ay-radius: 24px;
    --ease-out-expo: cubic-bezier(0.16, 1, 0.3, 1);
}

html, body { min-height: 100%; margin: 0; }
html {
    min-height: 100%;
    background-color: #101223;
    background-image: url('<?= htmlspecialchars($conf_bg_pc) ?>');
    background-position: center center;
    background-size: cover;
    background-repeat: no-repeat;
    background-attachment: fixed;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body.ay-bg {
    color: var(--ay-text);
    font-family: 'Inter', ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "PingFang SC", "Microsoft YaHei", sans-serif;
    min-height: 100vh;
    min-height: 100dvh;
    background: transparent;
    overflow-x: hidden;
    -webkit-font-smoothing: antialiased;
}

@media (max-width: 768px) {
    html { background-image: url('<?= htmlspecialchars($conf_bg_mobile) ?>'); background-attachment: scroll; }
}

/* 氛围背景与飘落花瓣 */
.ay-dim {
    position: fixed;
    inset: 0;
    pointer-events: none;
    background: rgba(0, 0, 0, .15);
    z-index: 0;
}
.ay-petals {
    position: fixed;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
    z-index: 1;
}
.ay-petals i {
    position: absolute;
    width: 12px;
    height: 10px;
    background: linear-gradient(135deg, #ffd1e6, #ff9aca);
    border-radius: 80% 80% 80% 20% / 80% 80% 20% 80%;
    opacity: .5;
    filter: blur(.2px);
    animation: ay-fall linear infinite;
    transform: rotate(20deg);
}
.ay-petals i:nth-child(3n) { width: 9px; height: 7px; animation-duration: 12s; }
.ay-petals i:nth-child(4n) { animation-duration: 10s; opacity: .35; }
.ay-petals i:nth-child(5n) { width: 14px; height: 12px; animation-duration: 14s; }
@keyframes ay-fall { to { transform: translateY(110vh) rotate(360deg); } }

/* 安装页同款半透明卡片与渐变描边 */
.ay-wrap {
    min-height: 100dvh;
    display: grid;
    /* 安全居中：内容高度超过视口时从顶部开始，避免卡片标题/头像被裁切 */
    place-items: safe center;
    padding: clamp(16px, 4vw, 32px);
    perspective: 1000px;
    position: relative;
    z-index: 10;
    padding-top: max(40px, env(safe-area-inset-top));
    padding-bottom: max(40px, env(safe-area-inset-bottom));
}
.ay-card {
    width: min(560px, 100%);
    background: var(--ay-card);
    border: 1px solid rgba(255,255,255,.28);
    border-radius: var(--ay-radius);
    box-shadow: 0 18px 50px rgba(0, 0, 0, .35);
    position: relative;
    overflow: hidden;
}
.ay-card::before {
    content: "";
    position: absolute;
    inset: -1px;
    border-radius: inherit;
    padding: 1px;
    background: conic-gradient(from 200deg, var(--ay-accent-start), var(--ay-accent-end), var(--ay-accent-start));
    -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    opacity: .68;
    pointer-events: none;
}

/* 头部 */
.ay-head {
    padding: 32px 26px 16px;
    display: grid;
    place-items: center;
    row-gap: 8px;
}
.ay-logo {
    width: 64px;
    height: 64px;
    border-radius: 20px;
    background-repeat: no-repeat;
    background-position: center;
    background-size: cover;
    box-shadow: 0 8px 26px rgba(255, 154, 202, .25);
    border: 1px solid rgba(255, 255, 255, .15);
}
.ay-title {
    margin: 8px 0 0;
    font-weight: 900;
    letter-spacing: .6px;
    font-size: clamp(20px, 2.6vw, 24px);
    color: #fff;
    text-shadow: 0 2px 8px rgba(0, 0, 0, .65);
    transition: all .3s;
}
.ay-sub {
    margin: 0 0 6px;
    color: #F0F2F7;
    text-shadow: 0 1px 3px rgba(0, 0, 0, .65);
    font-size: 12px;
    text-align: center;
    letter-spacing: 1px;
    transition: all .3s;
}
.ay-note { display: none; color: #DCE5F0; text-shadow: 0 1px 3px rgba(0,0,0,.65); }

/* 登录 / 邮箱验证滑动面板 */
/* 为浮动标签预留可见空间：避免“账号 / 邮箱”标签被滑动容器的 overflow 裁掉；负 margin 抵消 padding，不改变输入框原位置 */
#login-slider-container { overflow: hidden; width: 100%; position: relative; padding-top: 14px; margin-top: -14px; }
#login-slider {
    display: flex;
    width: 200%;
    transition: transform .5s var(--ease-out-expo);
    align-items: flex-start;
}
.slider-step { width: 50%; flex-shrink: 0; padding: 0 4px; transition: opacity .3s; }
.ay-body { padding: 0 32px 32px; position: relative; }

/* 表单输入框 */
.ay-field { position: relative; margin-bottom: 20px; }
.ay-input {
    width: 100%;
    height: var(--ay-input-h);
    padding: 12px 14px 12px 40px;
    border-radius: 12px;
    border: 1px solid var(--ay-stroke);
    background: var(--ay-input);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.20);
    color: var(--ay-text);
    outline: none;
    transition: all .25s var(--ease-out-expo);
    font-size: 13px;
}
.ay-input:-webkit-autofill {
    -webkit-text-fill-color: var(--ay-text) !important;
    transition: background-color 5000s ease-in-out 0s;
    box-shadow: inset 0 0 0 1000px rgba(255, 255, 255, .06) !important;
}
.ay-input::placeholder { color: transparent; }
.ay-label {
    position: absolute;
    left: 40px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 13px;
    color: #F0F2F7;
    text-shadow: 0 1px 3px rgba(0,0,0,.65);
    pointer-events: none;
    transition: all .2s cubic-bezier(.4, 0, .2, 1);
    font-weight: 600;
}
.ay-input:focus {
    border-color: rgba(255, 255, 255, .72);
    box-shadow: 0 0 0 3px rgba(205,235,255,.18), inset 0 1px 0 rgba(255,255,255,.34);
    background: rgba(255, 255, 255, .12);
}
.ay-input:focus ~ .ay-label,
.ay-input:not(:placeholder-shown) ~ .ay-label {
    top: -10px;
    left: 12px;
    font-size: 11px;
    line-height: 1;
    background: rgba(12, 14, 28, .85);
    padding: 3px 8px;
    border-radius: 999px;
    color: #FFFFFF;
    text-shadow: 0 1px 3px rgba(0,0,0,.65);
    border: 1px solid rgba(255, 255, 255, .15);
    transform: translateY(0);
    letter-spacing: .5px;
    z-index: 5;
}
.input-icon {
    position: absolute;
    left: 14px;
    color: rgba(255, 255, 255, .9);
    text-shadow: 0 1px 3px rgba(0,0,0,.65);
    font-size: 1.1rem;
    pointer-events: none;
    transition: color .3s ease;
    z-index: 2;
}
.ay-input:focus ~ .input-icon { color: #CDEBFF; }
.ay-eye {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: transparent;
    display: grid;
    place-items: center;
    cursor: pointer;
    transition: background .2s;
    border: none;
    color: #CDEBFF;
    z-index: 10;
}
.ay-eye:hover { background: rgba(255, 255, 255, .1); color: #fff; }
.ay-eye svg { transition: stroke .3s ease; }

/* 主按钮与文字链接 */
.ay-btn {
    width: 100%;
    height: var(--ay-input-h);
    border: 1px solid rgba(255,255,255,.55);
    border-radius: 12px;
    cursor: pointer;
    color: #fff;
    font-size: 14px;
    font-weight: 800;
    letter-spacing: 1px;
    margin-top: 8px;
    background: linear-gradient(135deg, rgba(255, 182, 240, .9), rgba(154, 214, 255, .9));
    box-shadow: 0 12px 25px rgba(122, 168, 255, .25), inset 0 1px 0 rgba(255, 255, 255, .78), inset 0 -1px 0 rgba(255,255,255,.18);
    position: relative;
    overflow: hidden;
    transition: all .2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    text-shadow: 0 1px 2px rgba(0, 0, 0, .1);
}
.ay-btn:not(:disabled):hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 30px rgba(122, 168, 255, .35), inset 0 1px 0 rgba(255, 255, 255, .7);
}
.ay-btn:not(:disabled):active { transform: translateY(1px) scale(.98); filter: brightness(.95); }
.ay-btn::after {
    content: "";
    position: absolute;
    top: -20%;
    bottom: -20%;
    left: -40%;
    right: -40%;
    pointer-events: none;
    background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,.3) 50%, rgba(255,255,255,0) 100%);
    transform: translateX(-120%) skewX(-20deg);
    transition: transform .6s ease;
}
.ay-btn:not(:disabled):hover::after { transform: translateX(140%) skewX(-20deg); }
.ay-btn:not(:disabled) { outline: 1px solid rgba(255,255,255,.14); outline-offset: 1px; }
.ay-btn:disabled {
    background: rgba(255, 255, 255, .08);
    color: rgba(255, 255, 255, .3);
    box-shadow: none;
    cursor: not-allowed;
    text-shadow: none;
    border: 1px solid rgba(255, 255, 255, .05);
}
.ay-foot {
    margin: 12px 0 8px;
    text-align: center;
    color: #F0F2F7;
    text-shadow: 0 1px 3px rgba(0,0,0,.65);
    font-size: 12px;
    opacity: .7;
    transition: opacity .2s;
    display: block;
    text-decoration: none;
}
.ay-foot:hover { opacity: 1; color: #CDEBFF; }
.ay-remember-row {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 9px;
    margin: -4px 2px 13px;
    color: rgba(255,255,255,.88);
    font-size: 12px;
    text-shadow: 0 1px 3px rgba(0,0,0,.65);
    cursor: pointer;
    user-select: none;
}
.ay-remember-row input {
    width: 15px;
    height: 15px;
    margin: 0;
    accent-color: #9bcfff;
    cursor: pointer;
}
.ay-remember-row span { line-height: 1.4; }
.ay-remember-hint { color: rgba(255,255,255,.62); font-size: 11px; margin-left: 2px; }
.ay-policy-links { margin: 12px 0 0; color: #DCE5F0; text-shadow: 0 1px 3px rgba(0,0,0,.65); font-size: 11px; line-height: 1.8; text-align: center; }
.ay-policy-links a { color: #CDEBFF; text-decoration: underline; text-underline-offset: 2px; margin: 0 5px; font-weight: 700; }
.ay-policy-links a:hover { color: #FFFFFF; }
.ay-policy-links span { color: rgba(255,255,255,.72); }
.spinner {
    display: inline-block;
    width: 18px;
    height: 18px;
    border: 2.5px solid rgba(255, 255, 255, .2);
    border-top-color: #fff;
    border-radius: 50%;
    animation: ay-spin .8s linear infinite;
}
@keyframes ay-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

/* 提示消息 */
.toast {
    position: fixed;
    top: 24px;
    left: 50%;
    transform: translate(-50%, -20px);
    opacity: 0;
    padding: 14px 24px;
    border-radius: 100px;
    background: rgba(12, 14, 28, .85);
    border: 1px solid rgba(255, 255, 255, .24);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 20px 40px rgba(0, 0, 0, .4);
    transition: .4s var(--ease-out-expo);
    z-index: 9999;
    color: #fff;
    pointer-events: none;
}
.toast.show { transform: translate(-50%, 0); opacity: 1; }

.fade-up { opacity: 0; transform: translateY(30px); animation: fadeUp .8s var(--ease-out-expo) forwards; }
@keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }

/* 矮屏/横屏/较小窗口：取消垂直居中，让页面自然向下延展并可滚动，避免顶部被挡住 */
@media (max-height: 760px) {
    .ay-wrap {
        place-items: start center;
        padding-top: max(18px, env(safe-area-inset-top));
        padding-bottom: max(24px, env(safe-area-inset-bottom));
    }
    .ay-head { padding-top: 24px; }
}

@media (max-width: 480px) {
    .ay-wrap {
        min-height: 100svh;
        padding: max(18px, env(safe-area-inset-top)) 14px max(20px, env(safe-area-inset-bottom));
    }
    .ay-card { width: min(440px, 100%); border-radius: 22px; }
    .ay-head { padding: 24px 20px 12px; row-gap: 6px; }
    .ay-logo { width: 56px; height: 56px; border-radius: 17px; }
    .ay-title { font-size: 21px; }
    .ay-sub { font-size: 12px; letter-spacing: .4px; }
    .ay-body { padding: 0 18px 22px; }
    .ay-field { margin-bottom: 17px; }
    .ay-input { height: 54px; font-size: 16px; border-radius: 14px; }
    .ay-btn { min-height: 54px; height: auto; border-radius: 14px; font-size: 15px; }
    .ay-eye { width: 40px; height: 40px; right: 7px; }
    .ay-foot { min-height: 38px; display: flex; align-items: center; justify-content: center; margin: 8px 0 0; }

    #ay-code { font-size: 20px !important; letter-spacing: 9px !important; }
    #login-step-2 > div[style*="display: flex"] { gap: 9px !important; }
    #login-step-2 .ay-btn { min-width: 0; }
    .toast { top: max(12px, env(safe-area-inset-top)); width: calc(100% - 28px); justify-content: center; text-align: center; }
}


/* GuYi 登录页布局：PC 左侧展示社区设备图，右侧为独立登录面板 */
.ay-login-layout {
    width: 100%;
    min-height: 100dvh;
    margin: 0 auto;
}
.ay-community-preview {
    display: block;
    width: min(88%, 460px);
    margin: 18px auto max(26px, env(safe-area-inset-bottom));
    position: relative;
    z-index: 2;
    pointer-events: none;
    user-select: none;
}
.ay-preview-image {
    display: block;
    width: 100%;
    height: auto;
    object-fit: contain;
    mix-blend-mode: screen;
    opacity: .97;
    filter: drop-shadow(0 22px 28px rgba(0,0,0,.30));
    animation: ay-device-float 8s ease-in-out infinite;
    -webkit-user-drag: none;
}
@keyframes ay-device-float {
    0%, 100% { transform: translate3d(0, 0, 0) rotate(-.25deg); }
    50% { transform: translate3d(0, -9px, 0) rotate(.2deg); }
}

/* 桌面端：设备预览在左，原生单列登录卡片在右 */
@media (min-width: 1024px) {
    .ay-login-layout {
        max-width: 1540px;
        min-height: 100dvh;
        padding: 24px clamp(24px, 3.8vw, 64px);
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(420px, 500px);
        column-gap: clamp(24px, 3.5vw, 58px);
        align-items: center;
    }
    .ay-wrap {
        grid-column: 2;
        grid-row: 1;
        width: 100%;
        min-height: 100dvh;
        padding: 24px 0;
        place-items: safe center;
    }
    .ay-card {
        width: min(500px, 100%);
        display: block;
        margin: 0;
    }
    .ay-head {
        display: grid;
        grid-template-columns: 1fr;
        justify-items: center;
        align-content: center;
        padding: 30px 26px 15px;
        border-right: 0;
        background: transparent;
    }
    .ay-logo { width: 64px; height: 64px; border-radius: 20px; }
    .ay-title { margin-top: 10px; font-size: 24px; }
    .ay-sub { font-size: 12px; letter-spacing: .35px; line-height: 1.6; }
    .ay-note { display: none; }
    .ay-body { display: block; min-width: 0; padding: 0 30px 30px; }
    .ay-body form { width: 100%; }
    #login-slider-container { max-width: 100%; margin: 0 auto; }

    .ay-community-preview {
        grid-column: 1;
        grid-row: 1;
        align-self: center;
        justify-self: center;
        width: 100%;
        max-width: 790px;
        margin: 0;
    }
    .ay-preview-image {
        max-height: 78vh;
        filter: drop-shadow(0 28px 36px rgba(0,0,0,.36));
    }
}

/* 移动端：登录区沿用原版布局；设备图只作为登录区之后的页尾内容 */
@media (max-width: 1023px) {
    .ay-login-layout { display: block; min-height: 100dvh; }
    .ay-wrap { min-height: 100dvh; }
    .ay-community-preview {
        width: min(88vw, 460px);
        margin: 18px auto max(28px, env(safe-area-inset-bottom));
    }
    .ay-preview-image { filter: drop-shadow(0 16px 22px rgba(0,0,0,.28)); }
}

@media (prefers-reduced-motion: reduce) {
    .ay-preview-image { animation: none; }
}


/* GuYi 管理端登录：仅居中卡片，不展示社区预览图 */
.ay-community-preview { display: none !important; }
@media (min-width: 1024px) {
    .ay-login-layout {
        display: block !important;
        max-width: none !important;
        padding: 0 !important;
    }
    .ay-wrap {
        grid-column: auto !important;
        grid-row: auto !important;
        min-height: 100dvh;
        place-items: center;
    }
    .ay-card { width: min(440px, 92vw); margin: 0 auto; }
}
</style>
</head>
<body class="ay-bg">
<div class="ay-dim" aria-hidden="true"></div>
<div class="ay-petals" aria-hidden="true">
    <i style="left:6%;top:-8vh;animation-duration:11s"></i>
    <i style="left:24%;top:-12vh;animation-duration:13s"></i>
    <i style="left:52%;top:-16vh;animation-duration:12s"></i>
    <i style="left:72%;top:-10vh;animation-duration:10s"></i>
    <i style="left:86%;top:-18vh;animation-duration:14s"></i>
</div>
<div class="ay-login-layout">
<main class="ay-wrap fade-up">
    <section class="ay-card" id="ay-card">
        <header class="ay-head">
            <div class="ay-logo" style="background-image:url('https://pub-8a73d0f678a64dc9abb02ea54e4c44c5.r2.dev/logo.png');" aria-hidden="true"></div>
            <h1 class="ay-title">登录 <?= htmlspecialchars($conf_site_title) ?></h1>
            <p class="ay-sub">请使用管理员账号继续访问控制台</p>
        </header>
        <div class="ay-body">
            <?php if (!empty($login_error)): ?>
            <div style="margin:0 0 14px;padding:12px 14px;border-radius:12px;background:rgba(239,68,68,.14);border:1px solid rgba(239,68,68,.35);color:#fecaca;font-size:13px;font-weight:600;text-align:center;">
                <?= htmlspecialchars($login_error) ?>
            </div>
            <?php endif; ?>
            <form method="POST" action="" autocomplete="on">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <div class="ay-field">
                    <input id="ay-user" class="ay-input" type="text" name="username" placeholder=" " required autocomplete="username" style="padding-left:42px;">
                    <svg class="input-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="left:14px;top:50%;transform:translateY(-50%);position:absolute;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <span class="ay-label" style="left:42px;">管理员账号</span>
                </div>
                <div class="ay-field">
                    <input id="ay-pwd" class="ay-input" type="password" name="password" placeholder=" " required autocomplete="current-password" style="padding-left:42px;padding-right:44px;">
                    <svg class="input-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="left:14px;top:50%;transform:translateY(-50%);position:absolute;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    <span class="ay-label" style="left:42px;">登录密码</span>
                    <button type="button" class="ay-eye" onclick="togglePwd('ay-pwd', this)" aria-label="显示或隐藏密码">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <button class="ay-btn" type="submit">立 即 登 录</button>
                <p class="ay-foot" style="margin-top:14px;"><?= htmlspecialchars($conf_site_title) ?> · Admin Console</p>
            </form>
        </div>
    </section>
</main>
</div>
<script>
function togglePwd(id, btn) {
    const input = document.getElementById(id);
    const icon = btn.querySelector('svg');
    if (!input || !icon) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
        icon.style.stroke = 'var(--ay-accent-start)';
    } else {
        input.type = 'password';
        icon.innerHTML = '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"/><circle cx="12" cy="12" r="3"/>';
        icon.style.stroke = '#CDEBFF';
    }
}
</script>
</body>
</html>
