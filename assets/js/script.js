
/* =========================================================
   SIAGAKAMPUNG
   MAIN JAVASCRIPT
   Version : 1.0 MVP
   Framework: Vanilla JavaScript + Bootstrap 5
   ========================================================= */

(function () {

    'use strict';

    /* =====================================================
       1. GLOBAL HELPERS
       ===================================================== */

    const $ = (selector, parent = document) => {
        return parent.querySelector(selector);
    };

    const $$ = (selector, parent = document) => {
        return Array.from(parent.querySelectorAll(selector));
    };

    const isElement = (element) => {
        return element instanceof HTMLElement;
    };


    /* =====================================================
       2. INITIALIZATION
       ===================================================== */

    function init() {

        initPasswordToggle();

        initMobileNavigation();

        initFormValidation();

        initFamilyChecklist();

        initTaskFilters();

        initTaskStatus();

        console.info('SIAGAKAMPUNG frontend initialized.');

    }


    /* =====================================================
       3. PASSWORD VISIBILITY
       Login form:
       #loginForm
       #password
       #togglePassword
       #eyeIcon
       ===================================================== */

    function initPasswordToggle() {

        const passwordInput = $('#password');

        const toggleButton = $('#togglePassword');

        const eyeIcon = $('#eyeIcon');

        if (!passwordInput || !toggleButton) {
            return;
        }

        toggleButton.addEventListener('click', function () {

            const isHidden = passwordInput.type === 'password';

            passwordInput.type = isHidden ? 'text' : 'password';

            toggleButton.setAttribute(
                'aria-pressed',
                String(isHidden)
            );

            toggleButton.setAttribute(
                'aria-label',
                isHidden
                    ? 'Sembunyikan password'
                    : 'Tampilkan password'
            );

            if (eyeIcon) {

                if (eyeIcon.classList.contains('bi-eye')) {

                    eyeIcon.classList.toggle('bi-eye', !isHidden);

                    eyeIcon.classList.toggle('bi-eye-slash', isHidden);

                } else if (eyeIcon.classList.contains('fa-eye')) {

                    eyeIcon.classList.toggle('fa-eye', !isHidden);

                    eyeIcon.classList.toggle('fa-eye-slash', isHidden);

                }

                eyeIcon.dataset.passwordVisible = String(isHidden);

            }

            passwordInput.focus();

        });

    }


    /* =====================================================
       4. MOBILE NAVIGATION
       Target: #mainNavbar

       Bootstrap handles the main collapse behavior.
       This script closes the menu after selecting a link.
       ===================================================== */

    function initMobileNavigation() {

        const navbar = $('#mainNavbar');

        if (!navbar) {
            return;
        }

        navbar.addEventListener('click', function (event) {

            const link = event.target.closest('a');

            if (!link) {
                return;
            }

            if (
                window.bootstrap &&
                window.bootstrap.Collapse &&
                navbar.classList.contains('show')
            ) {

                const collapse = window.bootstrap.Collapse
                    .getOrCreateInstance(navbar);

                collapse.hide();

            }

        });

    }


    /* =====================================================
       5. FORM VALIDATION
       Forms:
       - loginForm
       - familyForm
       - checklistForm
       - evacuationForm
       - taskForm
       - update-form
       ===================================================== */

    function initFormValidation() {

        const formSelectors = [
            '#loginForm',
            '#familyForm',
            '#checklistForm',
            '#evacuationForm',
            '#taskForm'
        ];

        const forms = [];

        formSelectors.forEach(function (selector) {

            const form = $(selector);

            if (form && !forms.includes(form)) {
                forms.push(form);
            }

        });

        $$('.update-form').forEach(function (form) {

            if (!forms.includes(form)) {
                forms.push(form);
            }

        });

        forms.forEach(function (form) {

            const memberCount = form.elements.namedItem('member_count');

            if (memberCount) {

                memberCount.addEventListener('input', function () {

                    validateMemberCount(memberCount);

                });

            }

            form.addEventListener('submit', function (event) {

                if (memberCount) {
                    validateMemberCount(memberCount);
                }

                form.classList.add('was-validated');

                if (!form.checkValidity()) {

                    event.preventDefault();

                    event.stopPropagation();

                    const firstInvalid = $(':invalid', form);

                    if (firstInvalid && isElement(firstInvalid)) {
                        firstInvalid.focus();
                    }

                }

            });

        });

    }


    /* =====================================================
       6. FAMILY MEMBER COUNT VALIDATION
       Input name: member_count
       Minimum: 1
       ===================================================== */

    function validateMemberCount(input) {

        const value = input.value.trim();

        if (value !== '' && Number(value) < 1) {

            input.setCustomValidity(
                'Jumlah anggota keluarga minimal 1 orang.'
            );

        } else {

            input.setCustomValidity('');

        }

    }


    /* =====================================================
       7. FAMILY READINESS CHECKLIST
       
       Form: #checklistForm

       Inputs:
       - emergency_bag
       - emergency_contacts
       - first_aid_kit
       - important_documents
       - assembly_point

       Progress:
       - #progressPercentage
       - #readinessProgressBar
       - #progressCount
       - #completeCount
       - #incompleteCount
       - #progressMessage
       ===================================================== */

    function initFamilyChecklist() {

        const checklistForm = $('#checklistForm');

        const inputs = checklistForm
            ? $$('.checklist-input', checklistForm)
            : $$('.checklist-input');

        if (!inputs.length) {
            return;
        }

        const TOTAL_ITEMS = 5;

        const progressPercentage = $('#progressPercentage');

        const progressBar = $('#readinessProgressBar');

        const progressCount = $('#progressCount');

        const completeCount = $('#completeCount');

        const incompleteCount = $('#incompleteCount');

        const progressMessage = $('#progressMessage');


        function updateChecklist() {

            const checkedCount = inputs.filter(function (input) {

                return input.checked;

            }).length;

            const percentage = Math.round(
                (checkedCount / TOTAL_ITEMS) * 100
            );

            const remaining = TOTAL_ITEMS - checkedCount;


            // Update percentage

            if (progressPercentage) {

                progressPercentage.textContent = percentage + '%';

            }


            // Update progress bar

            if (progressBar) {

                progressBar.style.width = percentage + '%';

                progressBar.setAttribute(
                    'aria-valuenow',
                    percentage
                );

                progressBar.setAttribute(
                    'aria-valuemin',
                    '0'
                );

                progressBar.setAttribute(
                    'aria-valuemax',
                    '100'
                );

            }


            // Update checklist count

            if (progressCount) {

                progressCount.textContent =
                    checkedCount + ' dari ' + TOTAL_ITEMS +
                    ' indikator terpenuhi';

            }


            // Update complete count

            if (completeCount) {

                completeCount.textContent = checkedCount;

            }


            // Update incomplete count

            if (incompleteCount) {

                incompleteCount.textContent = remaining;

            }


            // Update readiness message

            if (progressMessage) {

                let message = '';

                if (percentage === 100) {

                    message =
                        'Seluruh indikator checklist telah terpenuhi.';

                } else if (percentage >= 60) {

                    message =
                        'Sebagian besar indikator telah terpenuhi. Lengkapi sisanya.';

                } else if (percentage > 0) {

                    message =
                        'Masih terdapat beberapa indikator yang perlu dilengkapi.';

                } else {

                    message =
                        'Belum ada indikator checklist yang terpenuhi.';

                }

                progressMessage.textContent = message;

            }


            // Update individual checklist status

            inputs.forEach(function (input) {

                if (!input.name) {
                    return;
                }

                const statusElement = $(
                    '[data-status="' + input.name + '"]'
                );

                if (!statusElement) {
                    return;
                }

                if (input.checked) {

                    statusElement.textContent = 'Sudah siap';

                    statusElement.classList.add('status-verified');

                    statusElement.classList.remove('status-draft');

                } else {

                    statusElement.textContent = 'Belum siap';

                    statusElement.classList.remove('status-verified');

                    statusElement.classList.add('status-draft');

                }

            });

        }


        // Listen to checkbox changes

        inputs.forEach(function (input) {

            input.addEventListener('change', updateChecklist);

        });


        // Calculate initial progress

        updateChecklist();

    }


    /* =====================================================
       8. COMMUNITY TASK FILTER
       
       Filters:
       - all
       - pending
       - in_progress
       - completed

       Elements:
       - [data-filter]
       - #taskSearch
       - #taskTable
       - #taskTableBody
       - .task-row
       - #visibleCount
       - #emptyState
       ===================================================== */

    function initTaskFilters() {

        const filterButtons = $$('[data-filter]');

        const searchInput = $('#taskSearch');

        const taskTable = $('#taskTable');

        const taskRows = taskTable
            ? $$('.task-row', taskTable)
            : $$('.task-row');

        const visibleCount = $('#visibleCount');

        const emptyState = $('#emptyState');


        if (!filterButtons.length && !searchInput && !taskRows.length) {
            return;
        }


        let currentFilter = 'all';

        let searchKeyword = '';


        function updateTaskList() {

            let visible = 0;


            taskRows.forEach(function (row) {

                const rowStatus = (
                    row.dataset.status || ''
                ).toLowerCase();

                const rowSearch = (
                    row.dataset.search || row.textContent || ''
                ).toLocaleLowerCase('id');

                const matchesFilter =
                    currentFilter === 'all' ||
                    rowStatus === currentFilter;

                const matchesSearch =
                    rowSearch.includes(searchKeyword);

                const shouldShow =
                    matchesFilter && matchesSearch;

                row.hidden = !shouldShow;

                if (shouldShow) {
                    visible++;
                }

            });


            // Update visible task counter

            if (visibleCount) {

                visibleCount.textContent = visible;

            }


            // Update empty state

            if (emptyState) {

                emptyState.hidden = visible !== 0;

            }

        }


        // Filter button interaction

        filterButtons.forEach(function (button) {

            button.addEventListener('click', function (event) {

                if (button.tagName === 'A') {
                    event.preventDefault();
                }

                currentFilter = (
                    button.dataset.filter || 'all'
                ).toLowerCase();


                // Active filter styling

                filterButtons.forEach(function (item) {

                    const isActive = item === button;

                    item.classList.toggle('active', isActive);

                    item.setAttribute(
                        'aria-pressed',
                        String(isActive)
                    );

                });


                updateTaskList();

            });

        });


        // Search interaction

        if (searchInput) {

            searchInput.addEventListener('input', function () {

                searchKeyword = searchInput.value
                    .trim()
                    .toLocaleLowerCase('id');

                updateTaskList();

            });

        }


        // Initial list

        updateTaskList();

    }


    /* =====================================================
       9. COMMUNITY TASK STATUS UPDATE
       
       Form: .update-form

       Action: actions/task_status.php
       Method: POST

       Inputs:
       - task_id
       - status

       Allowed status:
       - pending
       - in_progress
       - completed
       ===================================================== */

    function initTaskStatus() {

        const updateForms = $$('.update-form');

        if (!updateForms.length) {
            return;
        }

        const allowedStatuses = [
            'pending',
            'in_progress',
            'completed'
        ];


        updateForms.forEach(function (form) {

            const statusInput = form.elements.namedItem('status');

            if (!statusInput) {
                return;
            }


            function validateStatus() {

                if (
                    !allowedStatuses.includes(statusInput.value)
                ) {

                    statusInput.setCustomValidity(
                        'Pilih status tugas yang tersedia.'
                    );

                } else {

                    statusInput.setCustomValidity('');

                }

            }


            statusInput.addEventListener(
                'change',
                validateStatus
            );


            form.addEventListener('submit', function (event) {

                validateStatus();

                if (!form.checkValidity()) {

                    event.preventDefault();

                    event.stopPropagation();

                    form.classList.add('was-validated');

                    return;

                }


                // Confirmation before completing a task

                if (statusInput.value === 'completed') {

                    const confirmed = window.confirm(
                        'Apakah Anda yakin ingin menandai tugas ini sebagai selesai?'
                    );

                    if (!confirmed) {

                        event.preventDefault();

                    }

                }

            });

        });

    }


    /* =====================================================
       10. START APPLICATION
       ===================================================== */

    if (document.readyState === 'loading') {

        document.addEventListener(
            'DOMContentLoaded',
            init
        );

    } else {

        init();

    }


    /* =====================================================
       END OF SIAGAKAMPUNG JAVASCRIPT
       ===================================================== */

})();
