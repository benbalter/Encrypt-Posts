jQuery(document).ready(function(a){pagenow=="post"&&encrypt_posts.prompt==1&&tb_show("Password","?TB_inline=true&inlineId=ep_password_prompt&modal=true");a("#ep_password_submit").click(function(){a("form#ep_password_form").submit();return false});a("#publish").click(function(b){if(a("#ep_toggle").is(":checked")&&a("#ep_password").val()=="")return b.preventDefault(),b.stopPropagation(),alert(encrypt_posts.noPassWarning),a("#ajax-loading").hide(),setTimeout("jQuery('#publish').removeClass('button-primary-disabled')",
1),false});a("#ep_toggle").is(":checked")||a("#ep_password_div").hide();a("#ep_toggle").change(function(){a("#ep_password_div").toggle("slow")});autosave=function(){}});