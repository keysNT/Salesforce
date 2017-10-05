/**
 * Created by magenest4 on 29/09/2017.
 */
Array.prototype.max = function() {
    return Math.max.apply(this, this);
};
function escapeRegExp(string) {
    return string.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
}
jQuery(document).ready(function () {
    var $report = jQuery('#report_sync').val();
    switch ($report){
        case 'lead':
            jQuery('#lead').show();
            jQuery('#account').hide();
            jQuery('#contact').hide();
            jQuery('#product').hide();
            jQuery('#order').hide();
            break;
        case 'account':
            jQuery('#lead').hide();
            jQuery('#account').show();
            jQuery('#contact').hide();
            jQuery('#product').hide();
            jQuery('#order').hide();
            break;
        case 'contact':
            jQuery('#lead').hide();
            jQuery('#account').hide();
            jQuery('#contact').show();
            jQuery('#product').hide();
            jQuery('#order').hide();
            break;
        case 'product':
            jQuery('#lead').hide();
            jQuery('#account').hide();
            jQuery('#contact').hide();
            jQuery('#product').show();
            jQuery('#order').hide();
            break;
        case 'order':
            jQuery('#lead').hide();
            jQuery('#account').hide();
            jQuery('#contact').hide();
            jQuery('#product').hide();
            jQuery('#order').show();
            break;
    }
    jQuery('#report_sync').on('change', function(event){
        var $report  = jQuery('#report_sync').val();
        if($report == 'lead'){

        }
        switch ($report){
            case 'lead':
                jQuery('#lead').show();
                jQuery('#account').hide();
                jQuery('#contact').hide();
                jQuery('#product').hide();
                jQuery('#order').hide();
                break;
            case 'account':
                jQuery('#lead').hide();
                jQuery('#account').show();
                jQuery('#contact').hide();
                jQuery('#product').hide();
                jQuery('#order').hide();
                break;
            case 'contact':
                jQuery('#lead').hide();
                jQuery('#account').hide();
                jQuery('#contact').show();
                jQuery('#product').hide();
                jQuery('#order').hide();
                break;
            case 'product':
                jQuery('#lead').hide();
                jQuery('#account').hide();
                jQuery('#contact').hide();
                jQuery('#product').show();
                jQuery('#order').hide();
                break;
            case 'order':
                jQuery('#lead').hide();
                jQuery('#account').hide();
                jQuery('#contact').hide();
                jQuery('#product').hide();
                jQuery('#order').show();
                break;
        }
    }  );
});
