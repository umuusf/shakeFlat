// ==============================================
//   CLOCK MANAGER
// ==============================================
const sfClockManager = {
    // 상수
    MONTH_NAMES: [ "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec" ],
    DAY_NAMES: [ "Sun","Mon","Tue","Wed","Thu","Fri","Sat" ],

    // DOM 요소 캐싱
    elements: {
        date: null,
        sec: null,
        min: null,
        hours: null
    },

    // 초기화
    init() {
        // DOM 요소 캐싱
        this.elements.date = $('#shakeflat-clock-date');
        this.elements.sec = $("#shakeflat-clock-sec");
        this.elements.min = $("#shakeflat-clock-min");
        this.elements.hours = $("#shakeflat-clock-hours");

        this.update();
    },

    // 시계 업데이트
    update() {
        const now = new Date();
        const seconds = now.getSeconds();
        const minutes = now.getMinutes();
        const hours = now.getHours();

        // 날짜 표시 업데이트
        this.elements.date.html(
            `${this.DAY_NAMES[now.getDay()]} ${now.getDate()} ${this.MONTH_NAMES[now.getMonth()]} ${now.getFullYear()}`
        );

        // 시간 표시 업데이트 (한 번에 처리)
        this.elements.sec.html(this.padZero(seconds));
        this.elements.min.html(this.padZero(minutes));
        this.elements.hours.html(this.padZero(hours));

        setTimeout(() => this.update(), 1000);
    },

    // 0 패딩 헬퍼 함수
    padZero(num) {
        return num < 10 ? "0" + num : num.toString();
    }
};

// ==============================================
//   LEFT MENU MANAGER
// ==============================================

const sfLeftMenuManager = {
    // 현재 상태를 저장하는 변수들
    currentMode: 'full',
    isLocked: false,
    isTemporaryFull: false,
    isHamburgerClickToFull: false,

    // 모드 상수
    MODE: {
        FULL: 'full',
        MINI: 'mini',
        HIDE: 'hide'
    },

    // DOM 요소 캐싱
    elements: {
        left: null,
        contentWrapper: null,
        clockDate: null,
        blind: null,
        hamburgerBtn: null
    },

    // 초기화
    init() {
        // DOM 요소 캐싱
        this.elements.left = $(".shakeflat-left");
        this.elements.contentWrapper = $(".shakeflat-content-wrapper");
        this.elements.clockDate = $("#shakeflat-clock-date");
        this.elements.blind = $(".shakeflat-sidebar-blind");
        this.elements.hamburgerBtn = $("#shakeflat-btn-left-menu");

        // 페이지 로딩 시 우선순위에 따른 상태 결정
        this.initializeState();
        this.applyMode();

        // 약간의 지연을 두고 아이콘 업데이트 (DOM이 완전히 로드된 후)
        setTimeout(() => this.updateHamburgerIcon(), 100);
    },

    // 페이지 로딩 시 상태 초기화 (우선순위 적용)
    initializeState() {
        // 로컬스토리지에서 기본 상태 로드
        this.isLocked = localStorage.getItem('sfLeftMenuLocked') === 'true';
        const savedMode = localStorage.getItem('sfLeftMenuMode');

        // 우선순위 1: 햄버거 락 상태인 경우 락 상태에 맞게 유지
        if (this.isLocked) {
            if (savedMode && Object.values(this.MODE).includes(savedMode)) {
                this.currentMode = savedMode;
            } else {
                // 락 상태이지만 저장된 모드가 없거나 유효하지 않은 경우 기본값
                this.currentMode = this.MODE.FULL;
            }
        } else {
            // 우선순위 2: 락 상태가 아닌 경우 window width에 의해 결정
            this.currentMode = this.getAutoMode();
        }
    },

    // 상태를 로컬스토리지에 저장
    saveState() {
        localStorage.setItem('sfLeftMenuLocked', this.isLocked.toString());
        localStorage.setItem('sfLeftMenuMode', this.currentMode);
    },

    // 윈도우 크기 기준값 상수
    BREAKPOINTS: {
        MOBILE: 600,
        TABLET: 1000
    },

    // 윈도우 크기에 따른 자동 모드 결정
    getAutoMode() {
        const width = $(window).width();
        if (width <= this.BREAKPOINTS.MOBILE) return this.MODE.HIDE;
        if (width <= this.BREAKPOINTS.TABLET) return this.MODE.MINI;
        return this.MODE.FULL;
    },

    // 윈도우 리사이즈 처리
    handleResize() {
        // 락 모드에서는 리사이즈에 반응하지 않음
        if (this.isLocked) return;

        const newMode = this.getAutoMode();
        const wasHamburgerClick = this.isHamburgerClickToFull;

        // 햄버거 클릭으로 인한 리사이즈이고 Full 모드가 될 때 플래그 유지
        if (wasHamburgerClick && newMode === this.MODE.FULL) {
            this.currentMode = newMode;
            this.applyMode();
        } else {
            // 일반적인 리사이즈인 경우 플래그 리셋
            this.isHamburgerClickToFull = false;
            this.currentMode = newMode;
            this.applyMode();
        }

        this.hideBlind();
        this.updateHamburgerIcon();
    },

    // 모드 적용
    applyMode(mode = null) {
        const targetMode = mode || this.currentMode;

        switch (targetMode) {
            case this.MODE.FULL:
                this.setFullMode();
                break;
            case this.MODE.MINI:
                this.setMiniMode();
                break;
            case this.MODE.HIDE:
                this.setHideMode();
                break;
        }

        if (mode) {
            this.currentMode = mode;
        }
    },

    // CSS 설정 헬퍼 메서드들
    setMenuDimensions(width, minWidth, display) {
        this.elements.left.css({
            "width": width,
            "min-width": minWidth,
            "display": display
        });
    },

    setMenuItemsVisibility(textDisplay, iconDisplay) {
        this.elements.left.find(".list-group > .list-group-item > .list-group-item-text").css("display", textDisplay);
        this.elements.left.find(".list-group > .list-group-item > .list-group-item-icon").css("display", iconDisplay);
    },

    setCollapsibleElements(hideToggle, hideCollapse, showOpened) {
        const toggle = this.elements.left.find(".list-group > [data-bs-toggle='collapse']");
        const collapse = this.elements.left.find(".list-group.collapse");
        const opened = this.elements.left.find(".list-group.opened");

        toggle[hideToggle ? 'addClass' : 'removeClass']('hide');
        collapse[hideCollapse ? 'removeClass' : 'removeClass']('show')[hideCollapse ? 'addClass' : 'removeClass']('hide');

        if (showOpened) {
            opened.removeClass('hide').addClass('show');
        }
    },

    // Full 모드 설정
    setFullMode() {
        this.setMenuDimensions("240px", "240px", "inline-block");
        this.elements.left.data("status", "full");

        this.setMenuItemsVisibility("inline-block", "inline-block");
        this.setCollapsibleElements(false, false, true);

        // 우선순위 3: 왼쪽메뉴가 열린 상태(full, 임시 full, full lock 등 모든 full 상태)에 대해서
        // 현재 선택된 서브메뉴는 항상 열린 상태로
        this.openActiveSubmenu();

        // 햄버거 클릭 플래그 리셋
        this.isHamburgerClickToFull = false;

        this.elements.contentWrapper.css("min-height", "calc(100% - 57px)");
        this.elements.clockDate.css("display", "block");
    },

    // Mini 모드 설정
    setMiniMode() {
        this.setMenuDimensions("60px", "60px", "inline-block");
        this.elements.left.data("status", "mini");

        this.setMenuItemsVisibility("none", "block");
        this.setCollapsibleElements(true, true, false);

        this.elements.contentWrapper.css("min-height", "calc(100% - 77px)");
        this.elements.clockDate.css("display", "block");
    },

    // Hide 모드 설정
    setHideMode() {
        this.elements.left.css("display", "none").data("status", "hide");
        this.elements.contentWrapper.css("min-height", "calc(100% - 77px)");
        this.elements.clockDate.css("display", "none");
    },

    // 현재 선택된 메뉴의 서브메뉴 열기
    openActiveSubmenu() {
        // 1. 현재 활성화된 서브메뉴 아이템 찾기
        const activeSubmenuItem = this.elements.left.find(".list-group.collapse .list-group-item.active");

        if (activeSubmenuItem.length > 0) {
            // 활성화된 서브메뉴 아이템의 부모 collapse 요소 찾기
            const parentCollapse = activeSubmenuItem.closest(".list-group.collapse");
            if (parentCollapse.length > 0) {
                parentCollapse.addClass("show");
            }
        }

        // 2. .active.open 클래스를 가진 주메뉴 찾기 (서브메뉴가 선택된 경우)
        const activeMainMenu = this.elements.left.find(".list-group > .list-group-item.active.open");
        if (activeMainMenu.length > 0) {
            const targetId = activeMainMenu.attr('data-bs-target');
            if (targetId) {
                const targetCollapse = this.elements.left.find(targetId);
                if (targetCollapse.length > 0) {
                    targetCollapse.addClass("show");
                }
            }
        }

        // 3. .active 클래스만 가진 메뉴 중에서 collapse를 가진 항목 찾기
        const activeMenuWithCollapse = this.elements.left.find(".list-group > .list-group-item.active[data-bs-target]");
        if (activeMenuWithCollapse.length > 0) {
            const targetId = activeMenuWithCollapse.attr('data-bs-target');
            if (targetId) {
                const targetCollapse = this.elements.left.find(targetId);
                if (targetCollapse.length > 0) {
                    targetCollapse.addClass("show");
                }
            }
        }

        // 4. 이미 show 클래스가 있는 collapse 요소들 유지 (서버에서 렌더링된 상태)
        const preOpenedCollapses = this.elements.left.find(".list-group.collapse.show");
        preOpenedCollapses.addClass("show");
    },

    // 임시로 열린 서브메뉴 닫기
    closeTemporarySubmenu() {
        // opened 클래스가 없고 현재 활성화되지 않은 collapse 요소들만 닫기
        this.elements.left.find(".list-group.collapse:not(.opened)").each(function() {
            const $collapse = $(this);
            // 현재 활성화된 서브메뉴가 있는 collapse는 유지
            const hasActiveItem = $collapse.find('.list-group-item.active').length > 0;
            if (!hasActiveItem) {
                $collapse.removeClass("show");
            }
        });
    },

    // 블라인드 표시/숨김
    showBlind() {
        this.elements.blind.css("display", "block");
    },

    hideBlind() {
        this.elements.blind.css("display", "none");
    },

    // 햄버거 버튼 아이콘 업데이트
    updateHamburgerIcon() {
        // 기존 자물쇠 아이콘 제거
        this.elements.hamburgerBtn.find('.lock-icon').remove();

        if (this.isLocked) {
            // 자물쇠 아이콘 추가 - 햄버거 버튼과 동일한 색상으로 스타일링
            const lockIcon = $('<i class="bi bi-lock-fill lock-icon"></i>').css({
                'position': 'absolute',
                'top': '-1px',
                'right': '2px',
                'font-size': '0.85rem',
                'color': 'var(--sf-topbar-text-color)',
                'z-index': '10',
                'border-radius': '3px',
                'padding': '1px 2px',
                'pointer-events': 'none'
            });
            this.elements.hamburgerBtn.append(lockIcon);
        }
    },

    // 햄버거 버튼 클릭 처리
    handleHamburgerClick() {
        const windowWidth = $(window).width();

        // 600px 이하에서는 락 기능 사용하지 않음
        if (windowWidth <= this.BREAKPOINTS.MOBILE) {
            this.handleMobileClick();
        } else {
            this.handleDesktopClick();
        }

        this.saveState();
        this.updateHamburgerIcon();
    },

    // 모바일 모드 클릭 처리
    handleMobileClick() {
        if (this.currentMode === this.MODE.HIDE) {
            this.currentMode = this.MODE.FULL;
            this.isLocked = false;
            this.isHamburgerClickToFull = true;
            this.applyMode();
            this.showBlind();
        } else {
            this.currentMode = this.MODE.HIDE;
            this.isLocked = false;
            this.applyMode();
            this.hideBlind();
        }
    },

    // 데스크톱 모드 클릭 처리
    handleDesktopClick() {
        switch (this.currentMode) {
            case this.MODE.FULL:
                if (this.isLocked) {
                    this.unlockAndResize();
                } else {
                    this.lockToMini();
                }
                break;

            case this.MODE.MINI:
                if (this.isLocked) {
                    this.unlockAndResize();
                } else {
                    this.lockToFull();
                }
                break;

            case this.MODE.HIDE:
                this.hideToFull();
                break;
        }
    },

    // 락 해제 및 자동 모드로 복귀
    unlockAndResize() {
        this.isLocked = false;
        this.isHamburgerClickToFull = true;
        this.handleResize();
    },

    // Mini 락 모드로 전환
    lockToMini() {
        this.currentMode = this.MODE.MINI;
        this.isLocked = true;
        this.applyMode();
        this.hideBlind();
    },

    // Full 락 모드로 전환
    lockToFull() {
        this.currentMode = this.MODE.FULL;
        this.isLocked = true;
        this.isHamburgerClickToFull = true;
        this.applyMode();
        if ($(window).width() <= this.BREAKPOINTS.TABLET) this.showBlind();
    },

    // Hide에서 Full로 전환
    hideToFull() {
        this.currentMode = this.MODE.FULL;
        this.isLocked = false;
        this.isHamburgerClickToFull = true;
        this.applyMode();
        this.showBlind();
    },

    // 마우스 오버 처리 (Mini/Mini락 모드에서만)
    handleMouseOver() {
        // 이미 일시적 Full 모드인 경우 무시
        if (this.isTemporaryFull) return;

        // Mini 모드 또는 Mini 락 모드에서만 일시적 Full 모드로 전환
        if (this.currentMode === this.MODE.MINI) {
            this.isTemporaryFull = true;
            this.setFullMode();
        }
    },

    // 마우스 아웃 처리 (일시적 Full 모드에서 원래 모드로 복귀)
    handleMouseOut() {
        // 일시적 Full 모드가 아닌 경우 무시
        if (!this.isTemporaryFull) return;

        // 임시로 열린 서브메뉴 닫기
        this.closeTemporarySubmenu();

        // 원래 모드로 복귀
        this.isTemporaryFull = false;
        this.applyMode();
    },

    // 블라인드 클릭 처리
    handleBlindClick() {
        const autoMode = this.getAutoMode();
        this.currentMode = (autoMode === this.MODE.HIDE) ? this.MODE.HIDE : this.MODE.MINI;
        this.applyMode();
        this.hideBlind();
    }
};

// ==============================================
//   THEME MANAGER (통합 및 효율화)
// ==============================================
const sfThemeManager = {
    // 상수
    THEMES: {
        LIGHT: 'light',
        DARK: 'dark',
        AUTO: 'auto'
    },

    // DOM 요소 캐싱
    elements: {
        dropdown: null,
        dropdownButton: null,
        dropdownItems: null
    },

    // 현재 상태
    currentTheme: 'light',
    currentSelect: 'auto',

    // 초기화
    init() {
        // DOM 요소 캐싱
        this.elements.dropdown = $(".shakeflat-theme-dropdown");
        this.elements.dropdownButton = this.elements.dropdown.find("> button");
        this.elements.dropdownItems = this.elements.dropdown.find(".dropdown-menu button");

        this.detectAndApplyTheme();
        this.setupThemeListener();
    },

    // 쿠키에서 테마 정보 가져오기
    getThemeCookie() {
        const cookie = Cookies.get('sfTheme');
        if (!cookie) return { theme: this.THEMES.LIGHT, select: this.THEMES.AUTO };

        try {
            const data = JSON.parse(cookie);
            return {
                theme: (data.theme === this.THEMES.DARK) ? this.THEMES.DARK : this.THEMES.LIGHT,
                select: [this.THEMES.LIGHT, this.THEMES.DARK, this.THEMES.AUTO].includes(data.select) ? data.select : this.THEMES.AUTO
            };
        } catch (e) {
            return { theme: this.THEMES.LIGHT, select: this.THEMES.AUTO };
        }
    },

    // 쿠키에 테마 정보 저장
    setThemeCookie(theme, select) {
        const data = { theme, select };
        Cookies.set('sfTheme', JSON.stringify(data), { expires: 3650, path: '/' });
    },

    // 시스템 다크 모드 감지
    isSystemDarkMode() {
        return window.matchMedia?.('(prefers-color-scheme: dark)').matches || false;
    },

    // 테마 결정 (select에 따라)
    determineTheme(select) {
        if (select === this.THEMES.AUTO) {
            return this.isSystemDarkMode() ? this.THEMES.DARK : this.THEMES.LIGHT;
        }
        return (select === this.THEMES.DARK || select === this.THEMES.LIGHT) ? select : this.THEMES.LIGHT;
    },

    // 테마 속성 적용
    applyThemeAttributes(theme) {
        const elements = [document.documentElement, document.body];
        const attrs = {
            'data-sf-theme': theme,
            'data-bs-theme': theme,
            'data-theme': theme
        };

        elements.forEach(element => {
            Object.entries(attrs).forEach(([attr, value]) => {
                element.setAttribute(attr, value);
            });
        });
    },

    // UI 업데이트
    updateUI(select) {
        const iconMap = {
            [this.THEMES.AUTO]: '<i class="bi bi-circle-half"></i> Auto',
            [this.THEMES.LIGHT]: '<i class="bi bi-sun-fill"></i> Light',
            [this.THEMES.DARK]: '<i class="bi bi-moon-stars-fill"></i> Dark'
        };

        this.elements.dropdownButton.html(iconMap[select] || iconMap[this.THEMES.AUTO]);
        this.elements.dropdownItems.removeClass("active");
        this.elements.dropdownItems.filter(`[data-sf-theme-value='${select}']`).addClass("active");
    },

    // 테마 설정 (외부 호출용)
    setTheme(select) {
        const theme = this.determineTheme(select);

        this.currentTheme = theme;
        this.currentSelect = select;

        this.applyThemeAttributes(theme);
        this.updateUI(select);
        this.setThemeCookie(theme, select);

        // Select2 테마 적용
        if (typeof sfApplySelect2Theme === 'function') {
            setTimeout(() => sfApplySelect2Theme(), 1);
        }
    },

    // 테마 감지 및 적용
    detectAndApplyTheme() {
        const { theme: cookieTheme, select: themeSelect } = this.getThemeCookie();

        this.currentSelect = themeSelect;

        if (themeSelect === this.THEMES.AUTO) {
            this.currentTheme = this.determineTheme(this.THEMES.AUTO);
            this.setThemeCookie(this.currentTheme, this.THEMES.AUTO);
        } else {
            this.currentTheme = themeSelect;
        }

        this.applyThemeAttributes(this.currentTheme);
        this.updateUI(this.currentSelect);
    },

    // 시스템 테마 변경 감지 설정
    setupThemeListener() {
        if (!window.matchMedia) return;

        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        const handleThemeChange = (e) => {
            // Auto 모드일 때만 시스템 테마 변경에 반응
            if (this.currentSelect === this.THEMES.AUTO) {
                this.currentTheme = e.matches ? this.THEMES.DARK : this.THEMES.LIGHT;
                this.applyThemeAttributes(this.currentTheme);
                this.setThemeCookie(this.currentTheme, this.THEMES.AUTO);

                // Select2 테마 적용
                if (typeof sfApplySelect2Theme === 'function') {
                    setTimeout(() => sfApplySelect2Theme(), 1);
                }
            }
        };

        // 모던 브라우저와 레거시 브라우저 모두 지원
        if (mediaQuery.addEventListener) {
            mediaQuery.addEventListener('change', handleThemeChange);
        } else {
            mediaQuery.addListener(handleThemeChange);
        }
    },

    // 현재 테마 반환 (외부 호출용)
    getTheme() {
        return this.currentTheme;
    }
};

// ==============================================
//   LEGACY FUNCTION COMPATIBILITY (하위 호환성)
// ==============================================

// 테마 관련 레거시 함수 (다른 파일에서 사용됨)
function sfGetTheme() { return sfThemeManager.getTheme(); }

// Select2 테마 적용 함수
function sfApplySelect2Theme() {
    const currentTheme = sfThemeManager.getTheme();

    // 모든 select2 드롭다운에 테마 클래스 적용
    $('.select2-dropdown').each(function() {
        $(this).attr('data-sf-theme', currentTheme);
    });

    // body에도 테마 속성 적용 (select2 드롭다운이 body에 append되는 경우를 위해)
    $('body').attr('data-sf-theme', currentTheme);
}

// Select2 초기화 후 테마 적용을 위한 함수
function sfInitSelect2WithTheme(selector, options = {}) {
    const $element = $(selector);
    if ($element.length === 0) return;

    // Select2 초기화
    $element.select2(options);

    // 드롭다운 열릴 때 테마 적용
    $element.on('select2:open', function() {
        setTimeout(() => {
            sfApplySelect2Theme();
        }, 1);
    });

    // 초기 테마 적용
    sfApplySelect2Theme();
}

// MutationObserver로 동적 select2 드롭다운 감지
function sfSetupSelect2Observer() {
    if (typeof MutationObserver === 'undefined') return;

    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // Element node
                    if ($(node).hasClass('select2-dropdown') || $(node).find('.select2-dropdown').length > 0) {
                        setTimeout(() => sfApplySelect2Theme(), 1);
                    }
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

// ==============================================
//   INITIALIZATION AND EVENT HANDLERS
// ==============================================

$(document).ready(function() {
    // 매니저들 초기화
    sfClockManager.init();
    sfLeftMenuManager.init();
    sfThemeManager.init();

    // Select2 테마 적용
    setTimeout(() => {
        if (typeof sfApplySelect2Theme === 'function') {
            sfApplySelect2Theme();
        }
        if (typeof sfSetupSelect2Observer === 'function') {
            sfSetupSelect2Observer();
        }
    }, 100);

    // 이벤트 핸들러 정의 및 바인딩
    const bindEvents = () => {
        // 햄버거 버튼
        $("#shakeflat-btn-left-menu").on("click", () => sfLeftMenuManager.handleHamburgerClick());

        // 블라인드
        $(".shakeflat-sidebar-blind").on("click", () => sfLeftMenuManager.handleBlindClick());

        // 메뉴 호버
        $(".shakeflat-left")
            .on("mouseover", () => sfLeftMenuManager.handleMouseOver())
            .on("mouseleave", () => sfLeftMenuManager.handleMouseOut());

        // 메뉴 아이템 클릭
        $(".shakeflat-left .list-group div.list-group-item").on("click", function() {
            if ($(this).data("bs-toggle") == "collapse") {
                $(this).toggleClass("open");

                // collapse 상태 업데이트
                const targetId = $(this).attr('data-bs-target');
                if (targetId) {
                    const targetCollapse = $(targetId);
                    if (targetCollapse.length > 0) {
                        targetCollapse.toggleClass("show");
                    }
                }
            }
            return false;
        });

        // 테마 드롭다운
        $(".shakeflat-theme-dropdown .dropdown-menu button").on("click", function() {
            sfThemeManager.setTheme($(this).data("sf-theme-value"));
        });

        // 윈도우 리사이즈
        $(window).on("resize", () => sfLeftMenuManager.handleResize());
    };

    bindEvents();
});
