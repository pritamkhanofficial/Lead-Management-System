<?= $this->extend('layout/default') ?>

<?= $this->section('content') ?>
<div class="container mt-4">
    <h2>Lead Management</h2>
    
    <?php if (session()->getFlashdata('message')): ?>
        <div class="alert alert-success">
            <?= session()->getFlashdata('message') ?>
        </div>
    <?php endif; ?>
    
    <div class="mb-3">
        <a href="<?= site_url('leads/create') ?>" class="btn btn-primary">Add New Lead</a>
        
        <!-- Import Form -->
        <form action="<?= site_url('leads/import') ?>" method="post" enctype="multipart/form-data" class="d-inline">
            <input type="file" name="excel_file" required>
            <button type="submit" class="btn btn-success">Import Leads</button>
        </form>
        
        <a href="<?= site_url('leads/export') ?>" class="btn btn-info">Export Leads</a>
        
        <!-- Add this near your import form -->
        <a href="<?= site_url('leads/download-template') ?>" class="btn btn-secondary">
            <i class="fas fa-download"></i> Download Import Template
        </a>
    </div>
    
    <table id="leadsTable" class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Date Added</th>
                <th>Actions</th>
            </tr>
        </thead>
    </table>
    
    <!-- < ?= $pager->links('default', 'bootstrap_5') ?> -->
</div>

<!-- Add these to your layout file or at the bottom of this view -->
<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
let dataTable;

$(document).ready(function() {
    dataTable = $('#leadsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= site_url('leads/ajax-list') ?>',
            type: 'POST'
        },
        columns: [
            { data: 'name' },
            { data: 'email' },
            { data: 'phone' },
            { 
                data: 'status',
                render: function(data) {
                    let badgeClass = {
                        'New': 'bg-primary',
                        'In Progress': 'bg-warning',
                        'Closed': 'bg-success'
                    };
                    return `<span class="badge ${badgeClass[data]}">${data}</span>`;
                }
            },
            { data: 'date_added' },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    return `
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-warning btn-sm" onclick="editLead(${row.id})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="deleteLead(${row.id})">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[4, 'desc']], // Sort by date_added by default
        pageLength: 10,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
        }
    });
});

function editLead(id) {
    window.location.href = `<?= site_url('leads/edit') ?>/${id}`;
}

function deleteLead(id) {
    if (confirm('Are you sure you want to delete this lead?')) {
        $.ajax({
            url: `<?= site_url('leads/delete') ?>/${id}`,
            type: 'POST',
            data: {
                _method: 'DELETE'
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Lead deleted successfully');
                    dataTable.ajax.reload();
                } else {
                    showAlert('error', response.message || 'Error deleting lead');
                }
            },
            error: function(xhr, status, error) {
                showAlert('error', 'Error deleting lead: ' + error);
                console.error(xhr.responseText);
            }
        });
    }
}

function showAlert(type, message) {
    const alertDiv = $(`<div class="alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show" role="alert">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>`);
    
    $('.container').prepend(alertDiv);
    
    // Auto-dismiss after 3 seconds
    setTimeout(() => {
        alertDiv.alert('close');
    }, 3000);
}
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?> 