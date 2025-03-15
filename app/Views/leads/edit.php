<?= $this->extend('layout/default') ?>

<?= $this->section('content') ?>
<div class="container mt-4">
    <h2>Edit Lead</h2>
    
    <?php if (session()->has('error')): ?>
        <div class="alert alert-danger">
            <?= session()->get('error') ?>
        </div>
    <?php endif; ?>
    
    <?php if (session()->has('errors')): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach (session('errors') as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form action="<?= site_url('leads/update/' . $lead['id']) ?>" method="post">
        <?= csrf_field() ?>
        
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control <?= session('errors.name') ? 'is-invalid' : '' ?>" 
                   id="name" name="name" value="<?= old('name', $lead['name']) ?>" required>
            <?php if (session('errors.name')): ?>
                <div class="invalid-feedback"><?= session('errors.name') ?></div>
            <?php endif; ?>
        </div>
        
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control <?= session('errors.email') ? 'is-invalid' : '' ?>" 
                   id="email" name="email" value="<?= old('email', $lead['email']) ?>" required>
            <?php if (session('errors.email')): ?>
                <div class="invalid-feedback"><?= session('errors.email') ?></div>
            <?php endif; ?>
        </div>
        
        <div class="mb-3">
            <label for="phone" class="form-label">Phone</label>
            <input type="text" class="form-control <?= session('errors.phone') ? 'is-invalid' : '' ?>" 
                   id="phone" name="phone" value="<?= old('phone', $lead['phone']) ?>" required>
            <?php if (session('errors.phone')): ?>
                <div class="invalid-feedback"><?= session('errors.phone') ?></div>
            <?php endif; ?>
        </div>
        
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-control <?= session('errors.status') ? 'is-invalid' : '' ?>" 
                    id="status" name="status" required>
                <?php foreach (['New', 'In Progress', 'Closed'] as $status): ?>
                    <option value="<?= $status ?>" 
                            <?= old('status', $lead['status']) === $status ? 'selected' : '' ?>>
                        <?= $status ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (session('errors.status')): ?>
                <div class="invalid-feedback"><?= session('errors.status') ?></div>
            <?php endif; ?>
        </div>
        
        <button type="submit" class="btn btn-primary">Update Lead</button>
        <a href="<?= site_url('leads') ?>" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<?= $this->endSection() ?> 