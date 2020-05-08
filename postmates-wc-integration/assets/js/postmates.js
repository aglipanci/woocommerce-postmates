jQuery(document).ready(function ($) {
  if ($('#woocommerce_postmates_title').length > 0) {
    var methods = [
      {
        'name': 'fixed',
        'id': 'woocommerce_postmates_driver_tip'
      },
      {
        'name': 'percentage',
        'id': 'woocommerce_postmates_driver_tip_percentage'
      }
    ];

    driverTipHandler();

    $('#woocommerce_postmates_driver_tip_method').on('change', driverTipHandler);
  }

  function driverTipHandler() {
    var selectedDriverTipMethod = $('#woocommerce_postmates_driver_tip_method').val()
    methods.forEach(function (method) {
      if (selectedDriverTipMethod !== method.name) {
        $('#' + method.id).closest('tr').hide();
      } else {
        $('#' + method.id).closest('tr').show();
      }
    });
  }
});
