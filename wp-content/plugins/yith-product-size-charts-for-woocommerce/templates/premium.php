<style>
.section{
    margin-left: -20px;
    margin-right: -20px;
    font-family: "Raleway",san-serif;
}
.section h1{
    text-align: center;
    text-transform: uppercase;
    color: #808a97;
    font-size: 35px;
    font-weight: 700;
    line-height: normal;
    display: inline-block;
    width: 100%;
    margin: 50px 0 0;
}
.section ul{
    list-style-type: disc;
    padding-left: 15px;
}
.section:nth-child(even){
    background-color: #fff;
}
.section:nth-child(odd){
    background-color: #f1f1f1;
}
.section .section-title img{
    display: table-cell;
    vertical-align: middle;
    width: auto;
    margin-right: 15px;
}
.section h2,
.section h3 {
    display: inline-block;
    vertical-align: middle;
    padding: 0;
    font-size: 24px;
    font-weight: 700;
    color: #808a97;
    text-transform: uppercase;
}

.section .section-title h2{
    display: table-cell;
    vertical-align: middle;
    line-height: 25px;
}

.section-title{
    display: table;
}

.section h3 {
    font-size: 14px;
    line-height: 28px;
    margin-bottom: 0;
    display: block;
}

.section p{
    font-size: 13px;
    margin: 25px 0;
}
.section ul li{
    margin-bottom: 4px;
}
.landing-container{
    max-width: 750px;
    margin-left: auto;
    margin-right: auto;
    padding: 50px 0 30px;
}
.landing-container:after{
    display: block;
    clear: both;
    content: '';
}
.landing-container .col-1,
.landing-container .col-2{
    float: left;
    box-sizing: border-box;
    padding: 0 15px;
}
.landing-container .col-1 img{
    width: 100%;
}
.landing-container .col-1{
    width: 55%;
}
.landing-container .col-2{
    width: 45%;
}
.premium-cta{
    background-color: #808a97;
    color: #fff;
    border-radius: 6px;
    padding: 20px 15px;
}
.premium-cta:after{
    content: '';
    display: block;
    clear: both;
}
.premium-cta p{
    margin: 7px 0;
    font-size: 14px;
    font-weight: 500;
    display: inline-block;
    width: 60%;
}
.premium-cta a.button{
    border-radius: 6px;
    height: 60px;
    float: right;
    background: url(<?php echo YITH_WCPSC_ASSETS_URL?>/images/upgrade.png) #ff643f no-repeat 13px 13px;
    border-color: #ff643f;
    box-shadow: none;
    outline: none;
    color: #fff;
    position: relative;
    padding: 9px 50px 9px 70px;
}
.premium-cta a.button:hover,
.premium-cta a.button:active,
.premium-cta a.button:focus{
    color: #fff;
    background: url(<?php echo YITH_WCPSC_ASSETS_URL?>/images/upgrade.png) #971d00 no-repeat 13px 13px;
    border-color: #971d00;
    box-shadow: none;
    outline: none;
}
.premium-cta a.button:focus{
    top: 1px;
}
.premium-cta a.button span{
    line-height: 13px;
}
.premium-cta a.button .highlight{
    display: block;
    font-size: 20px;
    font-weight: 700;
    line-height: 20px;
}
.premium-cta .highlight{
    text-transform: uppercase;
    background: none;
    font-weight: 800;
    color: #fff;
}

.section.one{
    background-image: url(<?php echo YITH_WCPSC_ASSETS_URL?>/images/01-bg.png); background-repeat: no-repeat; background-position: 85% 75%
}
.section.two{
    background-image: url(<?php echo YITH_WCPSC_ASSETS_URL?>/images/02-bg.png); background-repeat: no-repeat; background-position: 15% 100%
}
.section.three{
    background-image: url(<?php echo YITH_WCPSC_ASSETS_URL?>/images/03-bg.png); background-repeat: no-repeat; background-position: 85% 75%
}
.section.four{
    background-image: url(<?php echo YITH_WCPSC_ASSETS_URL?>/images/04-bg.png); background-repeat: no-repeat; background-position: 15% 100%
}
.section.five{
    background-image: url(<?php echo YITH_WCPSC_ASSETS_URL?>/images/05-bg.png); background-repeat: no-repeat; background-position: 85% 75%
}
.section.six{
    background-image: url(<?php echo YITH_WCPSC_ASSETS_URL?>/images/06-bg.png); background-repeat: no-repeat; background-position: 15% 100%
}
.section.seven{
    background-image: url(<?php echo YITH_WCPSC_ASSETS_URL?>/images/07-bg.png); background-repeat: no-repeat; background-position: 85% 75%
}
.section.eight{
    background-image: url(<?php echo YITH_WCPSC_ASSETS_URL?>/images/08-bg.png); background-repeat: no-repeat; background-position: 15% 100%
}
.section.nine{
    background-image: url(<?php echo YITH_WCPSC_ASSETS_URL?>/images/09-bg.png); background-repeat: no-repeat; background-position: 85% 75%
}
.section.ten{
    background-image: url(<?php echo YITH_WCPSC_ASSETS_URL?>/images/10-bg.png); background-repeat: no-repeat; background-position: 85% 75%
}

@media (max-width: 768px) {
    .section{margin: 0}
    .premium-cta p{
        width: 100%;
    }
    .premium-cta{
        text-align: center;
    }
    .premium-cta a.button{
        float: none;
    }
}

@media (max-width: 480px){
    .wrap{
        margin-right: 0;
    }
    .section{
        margin: 0;
    }
    .landing-container .col-1,
    .landing-container .col-2{
        width: 100%;
        padding: 0 15px;
    }
    .section-odd .col-1 {
        float: left;
        margin-right: -100%;
    }
    .section-odd .col-2 {
        float: right;
        margin-top: 65%;
    }
}

@media (max-width: 320px){
    .premium-cta a.button{
        padding: 9px 20px 9px 70px;
    }

    .section .section-title img{
        display: none;
    }
}
</style>
<div class="landing">
    <div class="section section-cta section-odd">
        <div class="landing-container">
            <div class="premium-cta">
                <p>
                    <?php echo sprintf( __('Upgrade to %1$spremium version%2$s of %1$sYITH Product Size Charts for WooCommerce%2$s to benefit from all features!','yith-product-size-charts-for-woocommerce'),'<span class="highlight">','</span>' );?>
                </p>
                <a href="<?php echo $this->get_premium_landing_uri() ?>" target="_blank" class="premium-cta-button button btn">
                    <span class="highlight"><?php _e('UPGRADE','yith-product-size-charts-for-woocommerce');?></span>
                    <span><?php _e('to the premium version','yith-product-size-charts-for-woocommerce');?></span>
                </a>
            </div>
        </div>
    </div>
    <div class="one section section-even clear">
        <h1><?php _e('Premium Features','yith-product-size-charts-for-woocommerce');?></h1>
        <div class="landing-container">
            <div class="col-1">
                <img src="<?php echo YITH_WCPSC_ASSETS_URL?>/images/01.png" alt="Product Size Charts" />
            </div>
            <div class="col-2">
                <div class="section-title">
                    <img src="<?php echo YITH_WCPSC_ASSETS_URL?>/images/01-icon.png" alt="icon 01"/>
                    <h2><?php _e('More than one size chart','yith-product-size-charts-for-woocommerce');?></h2>
                </div>
                <p>
                    <?php _e('Assign as many size charts as you wish to the same product: choose them directly from within the product, so that you can show your users several charts showing information in a tidy and customised way.', 'yith-product-size-charts-for-woocommerce');?>
                </p>
            </div>
        </div>
    </div>
    <div class="two section section-odd clear">
        <div class="landing-container">
            <div class="col-2">
                <div class="section-title">
                    <img src="<?php echo YITH_WCPSC_ASSETS_URL?>/images/02-icon.png" alt="icon 02" />
                    <h2><?php _e('Display mode','yith-product-size-charts-for-woocommerce');?></h2>
                </div>
                <p>
                    <?php echo sprintf(__('For each new size chart you can select one of the three different display modes available. You can display your chart as a %1$sWooCommerce tab%2$s, or in a modal window opening through the specific button added in product page or, finally, as a tabbed popup with two tabs, one for the chart, one for the description.', 'yith-product-size-charts-for-woocommerce'), '<b>', '</b>');?>
                </p>
            </div>
            <div class="col-1">
                <img src="<?php echo YITH_WCPSC_ASSETS_URL?>/images/02.png" alt="Display" />
            </div>
        </div>
    </div>
    <div class="three section section-even clear">
        <div class="landing-container">
            <div class="col-1">
                <img src="<?php echo YITH_WCPSC_ASSETS_URL?>/images/03.png" alt="Buttons" />
            </div>
            <div class="col-2">
                <div class="section-title">
                    <img src="<?php echo YITH_WCPSC_ASSETS_URL?>/images/03-icon.png" alt="icon 03" />
                    <h2><?php _e( 'Entirely customisable popup buttons','yith-product-size-charts-for-woocommerce');?></h2>
                </div>
                <p>
                    <?php echo sprintf(__('You can choose the position, in product page, for the %1$sbutton%2$s that will open the %1$schart popup%2$s.%3$sAnd what\'s more, you can also customise its colors and style as you prefer.', 'yith-product-size-charts-for-woocommerce'), '<b>', '</b>','<br>');?>
                </p>
            </div>
        </div>
    </div>
    <div class="four section section-odd clear">
        <div class="landing-container">
            <div class="col-2">
                <div class="section-title">
                    <img src="<?php echo YITH_WCPSC_ASSETS_URL?>/images/04-icon.png" alt="icon 04" />
                    <h2><?php _e('Popup settings ','yith-product-size-charts-for-woocommerce');?></h2>
                </div>
                <p>
                    <?php echo sprintf(__('Change the %1$sstyle of your size charts%2$s. Set colour, opening effects and graphic layout of your popup: besides the default one available, you can choose among %1$s3 premium templates%2$s for your popup and you can also edit overlay colour and opacity.', 'yith-product-size-charts-for-woocommerce'), '<b>', '</b>');?>
                </p>
            </div>
            <div class="col-1">
                <img src="<?php echo YITH_WCPSC_ASSETS_URL?>/images/04.png" alt="Popup" />
            </div>
        </div>
    </div>
    <div class="five section section-even clear">
        <div class="landing-container">
            <div class="col-1">
                <img src="<?php echo YITH_WCPSC_ASSETS_URL?>/images/05.png" alt="Chart style" />
            </div>
            <div class="col-2">
                <div class="section-title">
                    <img src="<?php echo YITH_WCPSC_ASSETS_URL?>/images/05-icon.png" alt="icon 05" />
                    <h2><?php _e('Chart style','yith-product-size-charts-for-woocommerce');?></h2>
                </div>
                <p>
                    <?php echo sprintf( __('Give %1$syour own style to the charts%2$s.%3$sChange their colour and graphic style: choose among %1$s3 different premium templates%2$s, besides default one, which can be either used in combination with popup styles or regardless of them.','yith-product-size-charts-for-woocommerce'),'<b>','</b>','<br>'); ?>
                </p>
            </div>
        </div>
    </div>
    <div class="six section section-odd clear">
        <div class="landing-container">
            <div class="col-2">
                <div class="section-title">
                    <img src="<?php echo YITH_WCPSC_ASSETS_URL?>/images/06-icon.png" alt="icon 06" />
                    <h2><?php _e('Advanced assign','yith-product-size-charts-for-woocommerce');?></h2>
                </div>
                <p>
                    <?php echo sprintf( __( 'Do you want to assign one or more charts to all products belonging to the %1$ssame category%2$s or to %1$sall products in your store%2$s?%3$sThis feature has been added for you, then!%2$sAssigning a size chart to a specific category or to all products and it will be displayed on all the products you want.','yith-product-size-charts-for-woocommerce' ),'<b>','</b>','<br>' ) ?>
                </p>
            </div>
            <div class="col-1">
                <img src="<?php echo YITH_WCPSC_ASSETS_URL?>/images/06.png" alt="Advanced assign" />
            </div>
        </div>
    </div>
    <div class="seven section section-even clear">
        <div class="landing-container">
            <div class="col-1">
                <img src="<?php echo YITH_WCPSC_ASSETS_URL?>/images/07.png" alt="Bulk Editing" />
            </div>
            <div class="col-2">
                <div class="section-title">
                    <img src="<?php echo YITH_WCPSC_ASSETS_URL?>/images/07-icon.png" alt="icon 07" />
                    <h2><?php _e('Bulk Editing','yith-product-size-charts-for-woocommerce');?></h2>
                </div>
                <p>
                    <?php echo sprintf( __('Assigning one or more size charts to more products at the same time is easy: select your products and, then, assign them the table you want using %1$sWordPress bulk editing tool%2$s.','yith-product-size-charts-for-woocommerce'),'<b>','</b>'); ?>
                </p>
            </div>
        </div>
    </div>
    <div class="eight section section-odd clear">
        <div class="landing-container">
            <div class="col-2">
                <div class="section-title">
                    <img src="<?php echo YITH_WCPSC_ASSETS_URL?>/images/08-icon.png" alt="icon 08" />
                    <h2><?php _e('Widget','yith-product-size-charts-for-woocommerce');?></h2>
                </div>
                <p>
                    <?php echo sprintf( __( 'A way to make all your tables created in your shop stand out.%3$sWith the widget %1$s"YITH Product Size Charts"%2$s, you\'ll be able to add a list with the tables created into the sidebars of your e-commerce, so that they can be highlighted for your users.','yith-product-size-charts-for-woocommerce' ),'<b>','</b>','<br>' ) ?>
                </p>
            </div>
            <div class="col-1">
                <img src="<?php echo YITH_WCPSC_ASSETS_URL?>/images/08.png" alt="Widget" />
            </div>
        </div>
    </div>
    <div class="nine section section-even clear">
        <div class="landing-container">
            <div class="col-1">
                <img src="<?php echo YITH_WCPSC_ASSETS_URL?>/images/09.png" alt="tinyMCE" />
            </div>
            <div class="col-2">
                <div class="section-title">
                    <img src="<?php echo YITH_WCPSC_ASSETS_URL?>/images/09-icon.png" alt="icon 07" />
                    <h2><?php _e('Shortcodes, PHP code, tinyMCE','yith-product-size-charts-for-woocommerce');?></h2>
                </div>
                <p>
                    <?php echo sprintf( __('Add your size charts easily into your e-commerce with the %1$sshortcodes%2$s available, using %1$sPHP code%2$s or using %1$stinyMCE editor%2$s.','yith-product-size-charts-for-woocommerce'),'<b>','</b>'); ?>
                </p>
            </div>
        </div>
    </div>
    <div class="eight section section-odd clear">
        <div class="landing-container">
            <div class="col-2">
                <div class="section-title">
                    <img src="<?php echo YITH_WCPSC_ASSETS_URL?>/images/10-icon.png" alt="icon 10" />
                    <h2><?php _e('Tab position','yith-product-size-charts-for-woocommerce');?></h2>
                </div>
                <p>
                    <?php echo sprintf( __( 'After creating your customized tab, choose where you want to place it among the tabs of the %1$sproduct page%2$s. One more chance to sort the content of your page.','yith-product-size-charts-for-woocommerce' ),'<b>','</b>','<br>' ) ?>
                </p>
            </div>
            <div class="col-1">
                <img src="<?php echo YITH_WCPSC_ASSETS_URL?>/images/10.png" alt="Tab position" />
            </div>
        </div>
    </div>
    <div class="section section-cta section-odd">
        <div class="landing-container">
            <div class="premium-cta">
                <p>
                    <?php echo sprintf( __('Upgrade to %1$spremium version%2$s of %1$sYITH Product Size Charts for WooCommerce%2$s to benefit from all features!','yith-product-size-charts-for-woocommerce'),'<span class="highlight">','</span>' );?>
                </p>
                <a href="<?php echo $this->get_premium_landing_uri() ?>" target="_blank" class="premium-cta-button button btn">
                    <span class="highlight"><?php _e('UPGRADE','yith-product-size-charts-for-woocommerce');?></span>
                    <span><?php _e('to the premium version','yith-product-size-charts-for-woocommerce');?></span>
                </a>
            </div>
        </div>
    </div>
</div>