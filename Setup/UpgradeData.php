<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 10/31/16 3:28 PM
 * @file: UpgradeData.php
 */
namespace Ebizmarts\MailChimp\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\DeploymentConfig;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var ResourceConnection
     */
    protected $_resource;
    /**
     * @var DeploymentConfig
     */
    protected $_deploymentConfig;
    public function __construct(ResourceConnection $resource,DeploymentConfig $deploymentConfig)
    {
        $this->_resource = $resource;
        $this->_deploymentConfig = $deploymentConfig;
    }
    
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.0.24') < 0)
        {
            $setup->startSetup();
            
            if ($this->_deploymentConfig->get(\Magento\Framework\Config\ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTIONS . '/sales')) {
                $connection = $this->_resource->getConnectionByName('sales');
            }
            else{
                $connection = $this->_resource->getConnectionByName('default');
            }

            $table = $connection->getTableName('sales_order');
            $select = $connection->select()
                ->from(
                    false,
                    ['mailchimp_flag' => new \Zend_Db_Expr('IF(mailchimp_abandonedcart_flag OR mailchimp_campaign_id OR mailchimp_landing_page, 1, 0)')]
                )->join(['O'=>$table],'O.entity_id = G.entity_id',[]);

            $query = $connection->updateFromSelect($select, ['G' => $connection->getTableName('sales_order_grid')]);
            $connection->query($query);

            $setup->endSetup();

        }
    }
}
