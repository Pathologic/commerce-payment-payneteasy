//<?php
/**
 * Payment PaynetEasy
 *
 * PaynetEasy payments processing
 *
 * @category    plugin
 * @version     0.0.1
 * @author      Pathologic
 * @internal    @events OnRegisterPayments,OnBeforeOrderProcessing,OnBeforeOrderSending,OnManagerBeforeOrderRender
 * @internal    @properties &title=Title;text; &token=Control;text; &login=Login;text; &endpoint=Endpoint;text; &debug=Debug;list;No==0||Yes==1;1 &production=Production mode;list;No==0||Yes==1;1
 * @internal    @modx_category Commerce
 * @internal    @installset base
 */

return require MODX_BASE_PATH . 'assets/plugins/payneteasy/plugin.payneteasy.php';
