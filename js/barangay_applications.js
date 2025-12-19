document.addEventListener('DOMContentLoaded', function(){
    const searchInput = document.getElementById('searchInput');
    const table = document.getElementById('applicationsTable');

    if(searchInput){
        searchInput.addEventListener('input', function(e){
            const q = e.target.value.trim().toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(r => {
                const name = (r.querySelector('.app-name') || {textContent:''}).textContent.toLowerCase();
                const idnum = (r.children[1] || {textContent:''}).textContent.toLowerCase();
                const contact = (r.children[2] || {textContent:''}).textContent.toLowerCase();
                if(q === '' || name.includes(q) || idnum.includes(q) || contact.includes(q)){
                    r.style.display = '';
                } else {
                    r.style.display = 'none';
                }
            });
        });
    }

    
    
    const modal = document.getElementById('applicationModal');
    const modalClose = modal ? modal.querySelector('.modal-close') : null;
    const approveBtn = document.getElementById('approveBtn');
    const rejectBtn = document.getElementById('rejectBtn');

    function showModal(){ if(modal) modal.style.display = 'block'; }
    function hideModal(){ if(modal) modal.style.display = 'none'; }

    document.addEventListener('click', function(e){
        const btn = e.target.closest && e.target.closest('.view-app-btn');
        if(btn){
            const id = btn.getAttribute('data-id');
            if(id) openApplication(parseInt(id,10));
        }
    });

    if(modalClose) modalClose.addEventListener('click', hideModal);
    if(modal) modal.querySelector('.modal-overlay').addEventListener('click', hideModal);

    function openApplication(id){
        fetch('php/get_application.php?id=' + encodeURIComponent(id))
            .then(r => r.json())
            .then(data => {
                if(!data || !data.success) return alert(data && data.error ? data.error : 'Unable to load');
                const app = data.application;
                document.getElementById('modalName').textContent = app.full_name || 'Application Details';
                document.getElementById('modalIdNumber').textContent = app.id_number || '';
                document.getElementById('modalContact').innerHTML = (app.contact_number||'') + '<br>' + (app.email||'');
                document.getElementById('modalLocation').textContent = (app.city||'') + ' / ' + (app.barangay||'') + ' / ' + (app.zone||'');
                document.getElementById('modalExperience').textContent = app.experience_years || '';
                document.getElementById('modalAvailability').textContent = app.availability || '';
                document.getElementById('modalVehicle').textContent = app.vehicle_access || '';
                document.getElementById('modalHealth').textContent = app.health_conditions || '<em>None</em>';
                document.getElementById('modalReason').textContent = app.reason_application || '';
                document.getElementById('modalSubmitted').textContent = app.submitted_at ? new Date(app.submitted_at).toLocaleString() : '';
                const docWrap = document.getElementById('modalDoc');
                docWrap.innerHTML = '';
                if(app.id_proof_path){
                    const link = document.createElement('a');
                    link.href = app.id_proof_path;
                    link.target = '_blank';
                    link.textContent = 'Open Document';
                    docWrap.appendChild(link);
                    if(/\.(jpg|jpeg|png)$/i.test(app.id_proof_path)){
                        const img = document.createElement('img');
                        img.src = app.id_proof_path;
                        img.style.maxWidth = '100%';
                        img.style.marginTop = '8px';
                        docWrap.appendChild(img);
                    }
                } else {
                    docWrap.textContent = 'No document uploaded.';
                }

                approveBtn.setAttribute('data-id', app.id);
                rejectBtn.setAttribute('data-id', app.id);
                showModal();
            })
            .catch(()=> alert('Network error'));
    }

    function handleAction(action, id){
        fetch('php/handle_application.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({id: id, action: action})
        }).then(r=>r.json()).then(data => {
            if(!data || !data.success) return alert(data && data.error ? data.error : 'Action failed');
            
            const row = document.querySelector('.view-app-btn[data-id="'+id+'"]').closest('tr');
            if(row){
                const statusCell = row.querySelector('.status-cell');
                if(statusCell) statusCell.textContent = data.status ? data.status.charAt(0).toUpperCase()+data.status.slice(1) : '';
            }
            
            hideModal();
        }).catch(()=> alert('Server error'));
    }

    if(approveBtn) approveBtn.addEventListener('click', function(){ const id = parseInt(this.getAttribute('data-id')||0,10); if(id) handleAction('accept', id); });
    if(rejectBtn) rejectBtn.addEventListener('click', function(){ const id = parseInt(this.getAttribute('data-id')||0,10); if(id) handleAction('reject', id); });
});
