(function () {
  'use strict';

  // —— 读服务端 meta ——
  function getMeta(name) {
    const el = document.querySelector('meta[name="' + name + '"]');
    return el ? el.content : '';
  }
  const loggedIn = getMeta('user-logged-in') === '1';
  const email    = getMeta('user-email');

  // —— 当前页作为 login 的 next 参数 ——
  function currentReturnUrl() {
    return location.pathname.replace(/^\//, '') + location.search;
  }

  function goLoginForTrial() {
    const rt = encodeURIComponent(currentReturnUrl());
    location.href = 'login.php?next=' + rt + '&trial=1';
  }

  // —— 打开 SciencePal 试用模态框 ——
  function openTrialModal() {
    if (document.querySelector('.trial-modal')) return;   // 防重复
    const wrap = document.createElement('div');
    wrap.className = 'trial-modal';

    const backdrop = document.createElement('div');
    backdrop.className = 'trial-modal__backdrop';
    backdrop.dataset.close = '1';

    const panel = document.createElement('div');
    panel.className = 'trial-modal__panel';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-labelledby', 'trial-modal-title');

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'trial-modal__x';
    closeBtn.setAttribute('aria-label', '关闭');
    closeBtn.dataset.close = '1';
    closeBtn.textContent = '×';

    const h3 = document.createElement('h3');
    h3.id = 'trial-modal-title';
    h3.textContent = '进入 SciencePal 试用';

    const p1 = document.createElement('p');
    p1.appendChild(document.createTextNode('使用你在 '));
    const strong1 = document.createElement('strong');
    strong1.textContent = 'MabSeek 注册的邮箱';
    p1.appendChild(strong1);
    if (email) {
      p1.appendChild(document.createTextNode(' '));
      const code = document.createElement('code');
      code.textContent = email;      // 用 textContent 防 XSS
      p1.appendChild(code);
    }
    p1.appendChild(document.createTextNode(' 与'));
    const strong2 = document.createElement('strong');
    strong2.textContent = '注册时的密码';
    p1.appendChild(strong2);
    p1.appendChild(document.createTextNode('登录 SciencePal。'));

    const p2 = document.createElement('p');
    p2.className = 'trial-modal__hint';
    p2.textContent = '若登录失败，请联系管理员补开通 SciencePal 账号。';

    const cta = document.createElement('a');
    cta.className = 'btn btn-green trial-modal__cta';
    cta.href = 'https://sciencepal.ai/login' + (email ? '?email=' + encodeURIComponent(email) : '');
    cta.target = '_blank';
    cta.rel = 'noopener';
    cta.textContent = '打开 SciencePal →';

    panel.append(closeBtn, h3, p1, p2, cta);
    wrap.append(backdrop, panel);
    document.body.appendChild(wrap);

    wrap.addEventListener('click', function (e) {
      if (e.target && e.target.dataset && e.target.dataset.close === '1') closeTrialModal();
    });
    document.addEventListener('keydown', escToClose);
  }

  function closeTrialModal() {
    const m = document.querySelector('.trial-modal');
    if (m) m.remove();
    document.removeEventListener('keydown', escToClose);
  }

  function escToClose(e) {
    if (e.key === 'Escape' || e.keyCode === 27) closeTrialModal();
  }

  // —— 拦截所有 [data-trial-cta] 点击 ——
  document.addEventListener('click', function (e) {
    const trigger = e.target.closest && e.target.closest('[data-trial-cta]');
    if (!trigger) return;
    e.preventDefault();
    if (loggedIn) openTrialModal();
    else goLoginForTrial();
  });

  // —— 页面加载：URL 含 trial=1 且已登录 → 自动弹 ——
  function autoOpenIfNeeded() {
    const params = new URLSearchParams(location.search);
    if (params.get('trial') === '1' && loggedIn) openTrialModal();
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', autoOpenIfNeeded);
  } else {
    autoOpenIfNeeded();
  }
})();
