<style>
    .whatsform-settings-page * {
        border-radius: 0 !important;
        box-sizing: border-box;
    }

    .whatsform-settings-page .left-bar h4 {
        width: 100%;
        box-shadow: 0px 1px 2px 0px rgb(0 0 0 / 20%);
    }

    .whatsform-settings-page #embed-whatsform-info {
        display: flex;
        justify-content: center;

    }

    .whatsform-settings-page #embed-whatsform-info>code {
        display: flex;
        justify-content: center;
        align-items: center;
        width: "fit-content"
    }
</style>
<div class="wrap whatsform-settings-page" style="display:flex;flex-direction:column;gap:20px;">
    <div style="border-bottom:1px solid lightgray;padding-bottom:10px;" class="header">
        <a href="https://whatsform.com" class="logo"><img src="<?php echo (WHATSFORM_DIR_URL . 'whatsform-logo.png'); ?>" height="30px" alt="whatsform logo" style="margin:10px 0px;" /></a>
        <br/>
        <a class="add-new-h2" target="_blank" href="<?php echo esc_url("https://www.youtube.com/watch?v=0mhTO2wGhOU"); ?>"><?php _e('Watch Tutorial', 'collectchat'); ?></a>

    </div>
    <div style="display:flex;justify-content:space-between;">
        <div class="left-bar" style="width:70%;margin-right:10px">
            <div>

                <div style="background-color:#f6f7f7;padding:10px 20px;margin-bottom:10px;border:1px solid #2271b1;"><?php echo __("Don't have a WhatsForm yet? <a href='https://whatsform.com/?utm_source=wordpress' target='_blank' rel='noreferrer'> Create a new one</a>") ?></div>
            </div>
            <?php if (count($errors) > 0) { ?>
                <div style="background-color:#ffe5e5;padding:10px 20px;margin-bottom:10px;border:1px solid red;">
                    <h4 style="margin:0;box-shadow:none"><?php echo __('Error ⚠️') ?></h4>

                    <ul>
                        <?php
                        array_map(function ($item) {
                            echo wp_kses("<li>&rarr; " . $item . "</li>", array('code' => array(), 'li' => array()));
                        }, $errors); ?>
                    </ul>
                </div>
            <?php } ?>
            <div style="background-color:white;margin-bottom:10px;">
                <h4 style="margin:0;padding:10px 20px;cursor:pointer;" onclick="whatsformHandleClick('insert-widget-item')"><?php echo __('💬 Add  widget'); ?></h4>
                <form class="item-body" id="insert-widget-item" action="options.php" method="post" style="display:block;padding:0 0 10px 0;border-top:1px solid lightgray;margin:0px 20px;">
                <?php
                    settings_fields("whatsform-settings-embed-widget");
                    do_settings_sections("whatsform-embed-widget");
                    submit_button("Save", "primary", "submit", true, !current_user_can( 'unfiltered_html') ? 'disabled' : '');
                ?>
                </form>

            </div>
            <div style="background-color:white;margin-bottom:10px;">
                <h4 onclick="whatsformHandleClick('inpost-widget-item')" style="margin:0;padding:10px 20px;cursor:pointer;" class="header-band"><?php echo __('💬 Add widget to specific pages / posts'); ?></h4>
                <div class="item-body" id="inpost-widget-item" style="display:none; padding: 15px 0;border-top:1px solid lightgray;margin:0px 20px;">
                    Copy the widget snippet from <a href="https://app.whatsform.com/?utm_source=wordpress" target="_blank" rel="noreferrer">WhatsForm dashboard</a> and paste it into <em>WhatsForm Widget</em> field inside the post/page editor.
                </div>

            </div>
            <div style="background-color:white;margin-bottom:10px;">
                <h4 onclick="whatsformHandleClick('generate-page-item')" style="margin:0;padding:10px 20px;cursor:pointer;" class="header-band"><?php echo __('📝 Set up embed in a new page'); ?></h4>

                <form class="item-body closed" action="options.php" method="post" id="generate-page-item" style="display:none;padding:0px 0px 10px 0px; border-top:1px solid lightgray;margin:0px 20px;">

                    <?php
                    settings_fields("whatsform-settings-generate-page");
                    do_settings_sections("whatsform-generate-page");
                    submit_button("Submit", "primary", "submit");
                    ?>
                </form>
            </div>
            <div style="background-color:white;margin-bottom:10px;">
                <h4 onclick="whatsformHandleClick('embed-whatsform-item')" style="margin:0;padding:10px 20px;cursor:pointer;" class="header-band"><?php echo __('📝 Set up embed in an existing page / post'); ?></h4>

                <div class="item-body" id="embed-whatsform-item" style="display:none;padding:20px 0; border-top:1px solid lightgray;margin:0px 20px;">

                    <div><?php echo __('Generate your shortcode and paste it into a page / post to embed WhatsForm'); ?> </div>
                    <div>
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th style="width:fit-content;">WhatsForm URL</th>
                                    <td>
                                        <input type="text" style="width:100%" id="embed-whatsform-input" placeholder="https://whatsform.com/<your_id>" value="<?php echo esc_attr((get_option('whatsform_url') === false ? '' : get_option('whatsform_url')));  ?>" />

                                    </td>
                                    <td>
                                        <div class="button button-primary" onclick="<?php echo esc_js('whatsformHandleGetShortcode()'); ?>">Get shortcode</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div id="embed-whatsform-info" style="display:none">
                            <code id="shortcode-container"><span id="shortcode-val"></span></code>
                            <div class="button" onclick="<?php echo esc_js('whatsformHandleCopyShortcode()'); ?>"><?php echo __('Copy'); ?></div>

                        </div>
                        <div id="copied-message" style="display:none;margin:5px 10px 0px 10px;justify-content:center;align-items:center;"><span><?php echo __('Copied'); ?></span></div>
                        <div id="validation-error-message" style="display:none;color:red;">
                            Invalid WhatsForm URL. Please enter a valid URL of the form <code style="color:black;">https://whatsform.com/&lt;form_id&gt;</code>
                        </div>

                    </div>


                </div>



            </div>





        </div>
        <div class="right-bar" style="width:30%">
            <div style="background-color:white;padding:20px;margin-bottom:10px;">
                <h4 style="margin:0;">Show us some love :)</h4>
                <div>
                    <p>Found WhatsForm useful? Rate it 5 stars and leave a nice little comment at wordpress.org. We would appreciate that.</p>
                    <p><a href="<?php echo esc_url("https://wordpress.org/support/plugin/whatsform/reviews/#new-post"); ?>" target="_blank" class="button" style="border-radius:0;">Rate 5 Stars</a></p>
                </div>
            </div>
            <div style="background-color:white;padding:20px;">
                <h4 style="margin:0;"><?php echo __("Let's be friends 👍"); ?></h4>
                <div>
                    <p>
                        <a href="<?php echo esc_url("https://www.youtube.com/c/NoCodeSchool?sub_confirmation=1"); ?>" target="_blank" class="button" style="border-radius:0;"><?php echo __('Subscribe on YouTube'); ?></a>
                        <a href="<?php echo esc_url("https://twitter.com/microdotcompany"); ?>" target="_blank" class="button" style="border-radius:0;"><?php echo __('Follow on Twitter'); ?></a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function whatsformHandleClick(id) {

        const focusElem = document.querySelector('#' + id)

        focusElem.style.display = focusElem.style.display === "block" ? "none" : "block";
    }

    function whatsformHandleCopyShortcode(e) {
        const el = document.createElement('textarea');
        el.value = document.getElementById("shortcode-container").innerText;
        el.setAttribute('readonly', '');
        el.style.position = 'absolute';
        el.style.left = '-9999px';
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        document.getElementById("copied-message").style.display = "flex";
        setTimeout(function() {
            document.getElementById("copied-message").style.display = "none";

        }, 1000);

    }

    function whatsformHandleGetShortcode() {

        const val = document.getElementById("embed-whatsform-input").value;
        const regexPattern = /^https:\/\/whatsform.com\/.{3,}$/i;
        if (regexPattern.test(val)) {
            document.getElementById("shortcode-val").innerText = '[whatsform id="' + val.substring(22) +
                '"]';
            document.getElementById("embed-whatsform-info").style.display = "flex";
            document.getElementById("validation-error-message").style.display = "none";


        } else if (val == '') {

            document.getElementById("embed-whatsform-info").style.display = "none";
            document.getElementById("validation-error-message").style.display = "none";
        } else {
            document.getElementById("embed-whatsform-info").style.display = "none";
            document.getElementById("validation-error-message").style.display = "block";
        }
    }
</script>