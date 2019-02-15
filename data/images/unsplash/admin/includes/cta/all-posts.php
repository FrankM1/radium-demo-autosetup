<div class="cta padding-bottom">
    <h3>Unsplash All Posts</h3>
    <button id="radium-unsplash-import-all">Import</button>
</div>

<script>
   jQuery(document).ready(function($) {

        // Load more button
        $('#radium-unsplash-import-all').on('click', function(e){

            var el = $(this);

            // If not saving, then proceed
            e.preventDefault();

            $.ajax({
                type: 'POST',
                url: usp_admin_localize.ajax_admin_url,
                data: {
                    action: 'radium_unsplush_upload_image',
                    nonce: usp_admin_localize.usp_admin_nonce,
                    source: 'unsplash',
                },
                success: function(response) {
                    console.log(response);
                },
                error: function(xhr, status, error) {
                    console.log(status);
                }

            });

        });

   });

</script>
