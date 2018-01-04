var resources = "{SITE_PUBLIC_PATH}resources/";

loadjscssfile(resources + "jquery/js/jquery-{jquery_version}.min.js", "js"); //Jquery
loadjscssfile(resources + "jquery_migrate/js/jquery-migrate-{jqueryMigrate_version}.min.js", "js"); //Jquery Migrate
loadjscssfile(resources + "jquery-ui-{jqueryui_version}.custom/js/jquery-ui-{jqueryui_version}.custom.js", "js"); //JqueryUI
loadjscssfile(resources + "jquery-ui-{jqueryui_version}.custom/css/{jqueryui_theme}/jquery-ui.min.css", "css"); //JqueryUI
