<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <form method="post">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="key" class="form-label">Word to Replace</label>
                        <input type="text" class="form-control" name="key" required placeholder="Enter word to replace">
                    </div>
                    <div class="col-md-6">
                        <label for="value" class="form-label">Replace With</label>
                        <input type="text" class="form-control" name="value" required placeholder="Enter replacement word">
                    </div>
                </div>
                <div class="text-center">
                    <button type="submit" name="add_to_dictionary" class="btn btn-success">
                        <i class="fas fa-plus mx-1"></i> Add to Dictionary
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="text-center mt-4">
    <form method="post">
        <button type="submit" name="clear_session" class="btn btn-danger">
            <i class="fas fa-trash-alt mx-1"></i> Clear Session & Reload
        </button>
    </form>
</div>