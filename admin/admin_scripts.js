const categories = {
    "دورات معهد الكويت للدراسات القضائية": { "الدورات الحسابية": ["لا يوجد"], "الدورات الهندسية": ["مدنية/معمارية", "ميكانيك", "كهرباء", "كمبيوتر"], "الدورات المشتركة": ["لا يوجد"], "دورات الخبراء الاشرافيين": ["رئيس قسم/مراقب/نائب رئيس"] },
    "التدريب اثناء العمل": { "التدريب الميداني": ["حسابي", "هندسي", "مشترك"], "التدريب داخل الادارة": ["لا يوجد"] },
    "البرامج التدريبية خارج الشئون الفنية": { "داخل الكويت": ["جميع الموظفين", "تخصصية", "رؤساء الاقسام", "المراقبين والمدراء", "ذات التقسيم اعلاه"], "خارج الكويت": ["لا يوجد"] }
};

let allWorkshopsData = [];
let activeSort = { column: 'start_datetime', direction: 'asc' };
let pastSort = { column: 'start_datetime', direction: 'desc' };

document.addEventListener('DOMContentLoaded', function() {
    setupCategoryDropdowns(document.getElementById('category_main'), document.getElementById('category_sub'), document.getElementById('category_specialization'));
    setupFilterDropdowns();
    loadAndRender();
    
    document.getElementById('workshopForm').addEventListener('submit', (e) => { e.preventDefault(); saveWorkshop(); });
    document.getElementById('searchInput').addEventListener('keyup', renderTables);
    document.querySelectorAll('.sortable-header').forEach(header => header.addEventListener('click', handleSortClick));
});

function setupCategoryDropdowns(mainCat, subCat, specCat) {
    mainCat.innerHTML = '<option value="">اختر...</option>' + Object.keys(categories).map(k => `<option value="${k}">${k}</option>`).join('');
    mainCat.onchange = () => {
        subCat.innerHTML = '<option value="">اختر...</option>';
        specCat.innerHTML = '<option value="">اختر...</option>';
        if (mainCat.value && categories[mainCat.value]) {
            subCat.innerHTML += Object.keys(categories[mainCat.value]).map(k => `<option value="${k}">${k}</option>`).join('');
        }
    };
    subCat.onchange = () => {
        specCat.innerHTML = '<option value="">اختر...</option>';
        if (mainCat.value && subCat.value && categories[mainCat.value][subCat.value]) {
            specCat.innerHTML += categories[mainCat.value][subCat.value].map(k => `<option value="${k}">${k}</option>`).join('');
        }
    };
}

function setupFilterDropdowns() {
    const mainFilter = document.getElementById('filterCategoryMain');
    const subFilter = document.getElementById('filterCategorySub');
    const specFilter = document.getElementById('filterCategorySpec');

    mainFilter.innerHTML = '<option value="all">كل التصنيفات الرئيسية</option>' + Object.keys(categories).map(k => `<option value="${k}">${k}</option>`).join('');

    mainFilter.onchange = () => {
        subFilter.innerHTML = '<option value="all">كل التصنيفات الفرعية</option>';
        specFilter.innerHTML = '<option value="all">كل التخصصات</option>';
        subFilter.disabled = true;
        specFilter.disabled = true;
        if (mainFilter.value !== 'all' && categories[mainFilter.value]) {
            subFilter.innerHTML += Object.keys(categories[mainFilter.value]).map(k => `<option value="${k}">${k}</option>`).join('');
            subFilter.disabled = false;
        }
        renderTables();
    };
    subFilter.onchange = () => {
        specFilter.innerHTML = '<option value="all">كل التخصصات</option>';
        specFilter.disabled = true;
        if (mainFilter.value !== 'all' && subFilter.value !== 'all' && categories[mainFilter.value][subFilter.value]) {
            specFilter.innerHTML += categories[mainFilter.value][subFilter.value].map(k => `<option value="${k}">${k}</option>`).join('');
            specFilter.disabled = false;
        }
        renderTables();
    };
    specFilter.onchange = renderTables;
}

function loadAndRender() {
    const activeTableBody = document.getElementById('active-workshop-table-body');
    const pastTableBody = document.getElementById('past-workshop-table-body');
    activeTableBody.innerHTML = '<tr><td colspan="6" class="text-center">جاري تحميل الورش...</td></tr>';
    pastTableBody.innerHTML = '<tr><td colspan="6" class="text-center">جاري تحميل...</td></tr>';
    
    fetch('api/admin_get_workshops.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Failed to load data.');
            allWorkshopsData = data.workshops;
            renderTables();
        })
        .catch(error => {
            console.error('Error fetching workshops:', error);
            activeTableBody.innerHTML = `<tr><td colspan="6" class="text-center">حدث خطأ: ${error.message}</td></tr>`;
            pastTableBody.innerHTML = '<tr><td colspan="6" class="text-center">حدث خطأ.</td></tr>';
        });
}

function handleSortClick(event) {
    const header = event.currentTarget;
    const tableContainer = header.closest('.table-container');
    if (!tableContainer) return; 
    
    const tableType = tableContainer.id.includes('active') ? 'active' : 'past';
    const column = header.dataset.sortBy;
    let sortState = tableType === 'active' ? activeSort : pastSort;

    if (sortState.column === column) {
        sortState.direction = sortState.direction === 'asc' ? 'desc' : 'asc';
    } else {
        sortState.column = column;
        sortState.direction = 'asc';
    }
    renderTables();
}

function renderTables() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const mainCatFilter = document.getElementById('filterCategoryMain').value;
    const subCatFilter = document.getElementById('filterCategorySub').value;
    const specCatFilter = document.getElementById('filterCategorySpec').value;

    let filteredData = allWorkshopsData.filter(w => {
        const titleMatch = w.title.toLowerCase().includes(searchTerm);
        const mainCatMatch = mainCatFilter === 'all' || (w.category_main || w.CATEGORY_MAIN) === mainCatFilter;
        const subCatMatch = subCatFilter === 'all' || subCatFilter === '' || (w.category_sub || w.CATEGORY_SUB) === subCatFilter;
        const specCatMatch = specCatFilter === 'all' || specCatFilter === '' || (w.category_specialization || w.CATEGORY_SPECIALIZATION) === specCatFilter;
        return titleMatch && mainCatMatch && subCatMatch && specCatMatch;
    });

    const activeWorkshops = filteredData.filter(w => w.STATUS === 'Scheduled');
    const pastWorkshops = filteredData.filter(w => w.STATUS === 'Completed' || w.STATUS === 'Cancelled');

    sortData(activeWorkshops, activeSort);
    sortData(pastWorkshops, pastSort);

    renderWorkshopTable(document.getElementById('active-workshop-table-body'), activeWorkshops, true);
    renderWorkshopTable(document.getElementById('past-workshop-table-body'), pastWorkshops, false);
    
    updateSortIcons();
}


function sortData(dataArray, sortState) {
    dataArray.sort((a, b) => {
        let valA = a[sortState.column] || a[sortState.column.toUpperCase()];
        let valB = b[sortState.column] || b[sortState.column.toUpperCase()];
        if (sortState.column === 'start_datetime') {
            return sortState.direction === 'asc' ? new Date(valA) - new Date(valB) : new Date(valB) - new Date(valA);
        }
        if (sortState.column === 'approved_count') {
             return sortState.direction === 'asc' ? parseInt(valA) - parseInt(valB) : parseInt(valB) - parseInt(valA);
        }
        return sortState.direction === 'asc' ? String(valA || '').localeCompare(String(valB || ''), 'ar') : String(valB || '').localeCompare(String(valA || ''), 'ar');
    });
}

function updateSortIcons() {
    document.querySelectorAll('#active-workshops-container .sortable-header, #past-workshops-container .sortable-header').forEach(header => {
        const tableType = header.closest('.table-container').id.includes('active') ? 'active' : 'past';
        const sortState = tableType === 'active' ? activeSort : pastSort;
        const icon = header.querySelector('.fa');
        icon.className = 'fa fa-sort';
        if (header.dataset.sortBy.toUpperCase() === sortState.column.toUpperCase()) {
            icon.className = sortState.direction === 'asc' ? 'fa fa-sort-asc' : 'fa fa-sort-desc';
        }
    });
}

function renderWorkshopTable(tableBody, data, isActiveTable) {
    tableBody.innerHTML = ''; 
    if (data.length === 0) {
        const message = isActiveTable ? 'لا توجد ورش تطابق البحث.' : 'لا توجد ورش سابقة.';
        tableBody.innerHTML = `<tr><td colspan="6" class="text-center">${message}</td></tr>`;
        return;
    }
    data.forEach(workshop => {
        const formattedDate = new Date(workshop.start_datetime.replace(' ', 'T')).toLocaleDateString('ar-KW');
        let pendingHtml = (workshop.pending_count > 0) ? ` / <span class="badge badge-warning">${workshop.pending_count} قيد الانتظار</span>` : '';
        let statusBadge = '';
        const status = workshop.STATUS || workshop.status;
        switch (status) {
            case 'Scheduled': statusBadge = `<span class="badge badge-success">مجدولة</span>`; break;
            case 'Completed': statusBadge = `<span class="badge badge-info">مكتملة</span>`; break;
            case 'Cancelled': statusBadge = `<span class="badge badge-danger">ملغاة</span>`; break;
            default: statusBadge = `<span class="badge badge-secondary">${status}</span>`;
        }
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${workshop.title}</td>
            <td>${workshop.category_main || '-'}</td>
            <td class="text-center">${formattedDate}</td>
            <td class="text-center"><span class="registrations-link">${workshop.approved_count} / ${workshop.max_capacity}</span>${pendingHtml}</td>
            <td class="text-center">${statusBadge}</td>
            <td class="action-buttons">
                <button class="btn-edit" title="تعديل"><i class="fa fa-pencil"></i></button>
                <button class="btn-view" title="عرض المسجلين"><i class="fa fa-users"></i></button>
                <button class="btn-delete" title="حذف"><i class="fa fa-trash"></i></button>
            </td>
        `;
        row.querySelector('.registrations-link').onclick = () => openRegistrationsModal(workshop.workshop_id, workshop.title);
        row.querySelector('.btn-edit').onclick = () => openWorkshopModal(workshop);
        row.querySelector('.btn-view').onclick = () => openRegistrationsModal(workshop.workshop_id, workshop.title);
        row.querySelector('.btn-delete').onclick = () => deleteWorkshop(workshop.workshop_id);
        tableBody.appendChild(row);
    });
}

function openWorkshopModal(workshop = null) {
    const form = document.getElementById('workshopForm');
    form.reset();
    document.getElementById('category_main').value = '';
    document.getElementById('category_main').dispatchEvent(new Event('change'));

    if (workshop) {
        document.getElementById('workshopModalTitle').innerText = 'تعديل ورشة عمل';
        document.getElementById('workshopId').value = workshop.workshop_id;
        document.getElementById('title').value = workshop.title;
        document.getElementById('description').value = workshop.description;
        document.getElementById('instructorName').value = workshop.instructor_name;
        const date = new Date(workshop.start_datetime.replace(' ', 'T'));
        date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
        document.getElementById('startDate').value = date.toISOString().slice(0, 16);
        document.getElementById('location').value = workshop.location;
        document.getElementById('maxCapacity').value = workshop.max_capacity;
        document.getElementById('status').value = workshop.STATUS || workshop.status;

        if (workshop.category_main) {
            document.getElementById('category_main').value = workshop.category_main;
            document.getElementById('category_main').dispatchEvent(new Event('change'));
            if (workshop.category_sub) {
                document.getElementById('category_sub').value = workshop.category_sub;
                document.getElementById('category_sub').dispatchEvent(new Event('change'));
                if (workshop.category_specialization) {
                    document.getElementById('category_specialization').value = workshop.category_specialization;
                }
            }
        }

    } else {
        document.getElementById('workshopModalTitle').innerText = 'إضافة ورشة جديدة';
        document.getElementById('workshopId').value = '';
    }
    $('#workshopModal').modal('show');
}

function saveWorkshop() {
    const form = document.getElementById('workshopForm');
    const formData = new FormData(form);
    const workshopId = formData.get('workshop_id');
    const url = workshopId ? 'api/admin_update_workshop.php' : 'api/admin_add_workshop.php';
    fetch(url, { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.success) { $('#workshopModal').modal('hide'); loadAndRender(); } 
        else { alert('Error: ' + data.message); }
    })
    .catch(error => console.error('Error saving workshop:', error));
}

function deleteWorkshop(id) {
    if (!confirm('هل أنت متأكد من رغبتك في حذف هذه الورشة؟ سيتم حذف جميع التسجيلات المتعلقة بها.')) return;
    fetch('api/admin_delete_workshop.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ workshop_id: id }) })
    .then(response => response.json())
    .then(data => {
        if (data.success) { loadAndRender(); } 
        else { alert('Error: ' + data.message); }
    });
}

function openRegistrationsModal(workshopId, workshopTitle) {
    document.getElementById('registrationsModalTitle').innerText = `المسجلون في: ${workshopTitle}`;
    const tableBody = document.getElementById('registrationsTableBody');
    tableBody.innerHTML = '<tr><td colspan="5" class="text-center">جاري التحميل...</td></tr>';
    const printBtn = document.getElementById('printAttendeesBtn');
    printBtn.onclick = () => printAttendees(workshopId);
    let modalSort = { column: 'full_name', direction: 'asc' };
    let registrationsData = [];
    const renderRegistrationsTable = () => {
        tableBody.innerHTML = '';
        if (registrationsData.length === 0) { 
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center">لا يوجد مسجلون حالياً.</td></tr>'; return;
        }
        registrationsData.sort((a, b) => {
            let valA = a[modalSort.column]; let valB = b[modalSort.column];
            if (modalSort.column === 'registration_date') { return modalSort.direction === 'asc' ? new Date(valA) - new Date(valB) : new Date(valB) - new Date(valA); }
            return modalSort.direction === 'asc' ? String(valA).localeCompare(String(valB), 'ar') : String(valB).localeCompare(String(valA), 'ar');
        });
        registrationsData.forEach(reg => {
            const row = document.createElement('tr');
            let statusBadge = ''; let actionButtons = '';
            const regDate = reg.registration_date ? new Date(reg.registration_date.replace(' ', 'T')).toLocaleString('ar-KW') : 'غير متوفر';
            switch (reg.status) {
                case 'Approved':
                    statusBadge = `<span class="badge badge-approved">مؤكد</span>`;
                    actionButtons = `<button class="btn btn-warning btn-sm">إلغاء التسجيل</button>`; break;
                case 'Pending':
                    statusBadge = `<span class="badge badge-pending">بانتظار الموافقة</span>`;
                    actionButtons = `<button class="btn btn-success btn-sm">قبول</button> <button class="btn btn-danger btn-sm">رفض</button>`; break;
                case 'Cancelled':
                    statusBadge = `<span class="badge badge-cancelled">ملغى</span>`; break;
            }
            // --- START OF CHANGE ---
            row.innerHTML = `
                <td>
                    ${reg.full_name}
                    <br>
                    <small class="text-muted">${reg.job_title || 'غير محدد'}</small>
                </td>
                <td>${reg.email}</td>
                <td>${regDate}</td>
                <td>${statusBadge}</td>
                <td>${actionButtons}</td>
            `;
            // --- END OF CHANGE ---
            if (reg.status === 'Approved') { row.querySelector('.btn-warning').onclick = () => updateRegistration(reg.registration_id, 'Cancelled', workshopId, workshopTitle); } 
            else if (reg.status === 'Pending') {
                row.querySelector('.btn-success').onclick = () => updateRegistration(reg.registration_id, 'Approved', workshopId, workshopTitle);
                row.querySelector('.btn-danger').onclick = () => updateRegistration(reg.registration_id, 'Cancelled', workshopId, workshopTitle);
            }
            tableBody.appendChild(row);
        });
    };
    document.querySelectorAll('#registrationsModal .sortable-header').forEach(header => {
        header.onclick = () => {
            const column = header.dataset.sortBy;
            if (modalSort.column === column) { modalSort.direction = modalSort.direction === 'asc' ? 'desc' : 'asc'; } 
            else { modalSort.column = column; modalSort.direction = 'asc'; }
            renderRegistrationsTable();
        };
    });
    
    const cacheBuster = new Date().getTime();
    fetch(`api/admin_get_registrations.php?workshop_id=${workshopId}&t=${cacheBuster}`)
        .then(response => { 
            if (!response.ok) { throw new Error(`HTTP error! Status: ${response.status}`); } 
            return response.json(); 
        })
        .then(data => { 
            registrationsData = data; 
            renderRegistrationsTable(); 
        })
        .catch(error => {
            console.error('Error fetching registrations:', error);
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center">حدث خطأ أثناء تحميل بيانات المسجلين.</td></tr>';
        });
    
    $('#registrationsModal').modal('show');
}

function updateRegistration(regId, newStatus, workshopId, workshopTitle) {
    fetch('api/admin_update_registration.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ registration_id: regId, status: newStatus }) })
    .then(response => response.json())
    .then(data => {
        if (data.success) { 
            loadAndRender();
            openRegistrationsModal(workshopId, workshopTitle); 
        } 
        else { alert('Error: ' + data.message); }
    });
}

function printAttendees(workshopId) {
    window.open(`print_attendees.html?workshop_id=${workshopId}`, '_blank');
}