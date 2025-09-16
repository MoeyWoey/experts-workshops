document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('userSearchInput');
    const resultsContainer = document.getElementById('results-container');
    let searchTimeout;

    searchInput.addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const searchTerm = searchInput.value.trim();
            if (searchTerm.length > 0) { // Changed from > 2 to > 0
                searchUsers(searchTerm);
            } else {
                resultsContainer.innerHTML = ''; // Clear results if search is empty
            }
        }, 300); // Wait 300ms after user stops typing
    });

    function searchUsers(term) {
        resultsContainer.innerHTML = '<p class="text-center mt-4">جاري البحث...</p>';
        fetch(`api/admin_get_user_details.php?search=${encodeURIComponent(term)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                renderResults(data);
            })
            .catch(error => {
                console.error('Error fetching user data:', error);
                resultsContainer.innerHTML = '<p class="text-center mt-4 text-danger">حدث خطأ أثناء البحث.</p>';
            });
    }

    function renderResults(users) {
        resultsContainer.innerHTML = '';
        if (!users || users.length === 0) {
            resultsContainer.innerHTML = '<p class="text-center mt-4 text-muted">لم يتم العثور على مستخدمين يطابقون هذا البحث.</p>';
            return;
        }

        users.forEach(user => {
            const userCard = document.createElement('div');
            userCard.className = 'user-card';

            const upcomingWorkshopsHtml = user.workshops.upcoming.length > 0 ?
                user.workshops.upcoming.map((w, index) => `<tr><td>${index + 1}</td><td>${w.title}</td><td>${new Date(w.start_datetime).toLocaleDateString('ar-KW')}</td><td><span class="badge badge-info">${w.registration_status}</span></td></tr>`).join('') :
                '<tr><td colspan="4" class="text-center text-muted">لا يوجد ورش قادمة.</td></tr>';

            const pastWorkshopsHtml = user.workshops.past.length > 0 ?
                user.workshops.past.map((w, index) => `<tr><td>${index + 1}</td><td>${w.title}</td><td>${new Date(w.start_datetime).toLocaleDateString('ar-KW')}</td><td><span class="badge badge-success">${w.registration_status}</span></td></tr>`).join('') :
                '<tr><td colspan="4" class="text-center text-muted">لا يوجد ورش سابقة.</td></tr>';

            userCard.innerHTML = `
                <h4>${user.full_name} <small class="text-muted">(ID: ${user.user_id})</small></h4>
                <p class="text-muted">${user.email} | ${user.job_title || 'غير محدد'}</p>
                <hr>
                <h5>الورش المسجل بها (القادمة)</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead><tr><th>#</th><th>الورشة</th><th>التاريخ</th><th>الحالة</th></tr></thead>
                        <tbody>${upcomingWorkshopsHtml}</tbody>
                    </table>
                </div>
                <h5 class="mt-4">الورش السابقة</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead><tr><th>#</th><th>الورشة</th><th>التاريخ</th><th>الحالة</th></tr></thead>
                        <tbody>${pastWorkshopsHtml}</tbody>
                    </table>
                </div>
            `;
            resultsContainer.appendChild(userCard);
        });
    }
});

