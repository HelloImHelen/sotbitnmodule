<script>
    window.INFOInstallHelper = function (params) {
        this.module = 'sotbit.info';
        this.sessid = params.sessid;
        this.upRand = 0;
        this.startDownload();
    }

    window.INFOInstallHelper.prototype = {
        startDownload: function () {
            this.installUpdates();
        },

        installUpdates: function () {
            CHttpRequest.Action = (result) => {
                this.installUpdatesAction(result);
            }
            this.upRand++;
            CHttpRequest.Send(`/bitrix/admin/update_system_partner_call.php?reqm=${this.module}&${this.sessid}&query_type=M&updRand=${this.upRand}`);
        },

        installUpdatesAction: function (result) {
            result = this.prepareString(result);
            const code = result.substring(0, 3);
            const data = result.substring(3);

            if (code === "STP") {
                const arData = data.split("|");
                this.installUpdates();
            }
        },

        prepareString: function (str) {
            str = str.replace(/^\s+|\s+$/, '');
            while (str.length > 0 && str.charCodeAt(0) == 65279)
                str = str.substring(1);
            return str;
        },

    }

    new INFOInstallHelper({
        sessid: '<?=bitrix_sessid_get()?>',
    });
</script>
