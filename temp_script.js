
        // Define critical functions immediately in head to avoid "not defined" errors
        function generateSlugModal(name) {
            const slug = name.toLowerCase()
                .replace(/[^a-z0-9 -]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim();
            const slugInput = document.getElementById('modal_slug');
            if (slugInput) {
                slugInput.value = slug;
            }
        }
        
        function loadCategoryDataModal(categoryId) {
            fetch(`get_categories.php?id=${categoryId}`, {
                credentials: 'include'
            })
            .then(response => response.json())
            .then(result => {
                if (result.success && result.category) {
                    const category = result.category;
                    document.getElementById('modal_category_id').value = category.id;
                    document.getElementById('modal_name').value = category.name;
                    document.getElementById('modal_slug').value = category.slug || '';
                    document.getElementById('modal_description').value = category.description || '';
                    document.getElementById('modal_parent_id').value = category.parent_id || '';
                    document.getElementById('modal_sort_order').value = category.sort_order || 0;
                    document.getElementById('modal_seo_title').value = category.seo_title || '';
                    document.getElementById('modal_seo_description').value = category.seo_description || '';
                    document.getElementById('modal_is_active').checked = category.is_active == 1;
                }
            })
            .catch(error => console.error('Failed to load category:', error));
        }
        
        function openCategoryModal(categoryId = null) {
            // This will be properly implemented when full script loads
            // But makes the function available immediately for onclick attributes
            const modal = document.getElementById('categoryModal');
            if (!modal) {
                console.log('Modal not ready, waiting for DOM...');
                document.addEventListener('DOMContentLoaded', function() {
                    openCategoryModal(categoryId);
                });
                return;
            }
            
            const title = document.getElementById('categoryModalTitle');
            const saveText = document.getElementById('categorySaveText');
            const form = document.getElementById('categoryModalForm');
            
            form.reset();
            document.getElementById('modal_category_id').value = '';
            document.getElementById('modal_is_active').checked = true;
            document.getElementById('modal_sort_order').value = 0;
            
            if (categoryId) {
                title.innerHTML = '<i class="fas fa-edit"></i> Edit Category';
                saveText.textContent = 'Update Category';
                document.getElementById('modal_action').value = 'update';
                loadCategoryDataModal(categoryId);
            } else {
                title.innerHTML = '<i class="fas fa-plus"></i> Add New Category';
                saveText.textContent = 'Create Category';
                document.getElementById('modal_action').value = 'create';
            }
            
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10);
            setTimeout(() => document.getElementById('modal_name').focus(), 100);
        }
        
        function closeCategoryModal() {
            const modal = document.getElementById('categoryModal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => modal.style.display = 'none', 300);
            }
        }
        
        // Make available globally
        window.openCategoryModal = openCategoryModal;
        window.closeCategoryModal = closeCategoryModal;
        window.generateSlugModal = generateSlugModal;
        window.loadCategoryDataModal = loadCategoryDataModal;
    
