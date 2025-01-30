<h3 class="text-center mt-4">Add to Dictionary</h3>
<form method="post" class="mt-4">
    <div class="row mb-3"> <!-- Ganti form-row dengan row -->
        <div class="col-md-5">
            <label for="key" class="form-label">Word to Replace</label>
            <input type="text" class="form-control" name="key" required>
        </div>
        <div class="col-md-5">
            <label for="value" class="form-label">Replace With</label>
            <input type="text" class="form-control" name="value" required>
        </div>
        <div class="col-md-2 align-self-end">
            <button type="submit" name="add_to_dictionary" class="btn btn-success">Add</button>
        </div>
    </div>
</form>

<div class="text-center mt-4">
    <form method="post">
        <button type="submit" name="clear_session" class="btn btn-danger">Clear Session & Reload</button>
    </form>
</div>

<span><?= $_SESSION['file_name'] ?></span>