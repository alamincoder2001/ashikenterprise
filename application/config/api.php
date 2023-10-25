<?php
defined('BASEPATH') or exit('No direct script access allowed');

$route['api/v1/say_hi']      = 'ApiController/TestController/sayHi';
// Login
$route['api/v1/login']      = 'ApiController/AuthController/login';

// User
$route['api/v1/userSignup'] = 'ApiController/UserController/userSignup';
$route['api/v1/getUsers']   = 'ApiController/UserController/getUsers';

// Page Controller
$route['api/v1/getBranches']         = 'ApiController/PageController/getBranches';
$route['api/v1/getCategories']       = 'ApiController/PageController/getCategories';
$route['api/v1/addCategory']         = 'ApiController/PageController/addCategory';
$route['api/v1/getBrands']           = 'ApiController/PageController/getBrands';
$route['api/v1/getUnits']            = 'ApiController/PageController/getUnits';
$route['api/v1/getDistricts']        = 'ApiController/PageController/getDistricts';
$route['api/v1/getCompanyProfile']   = 'ApiController/PageController/getCompanyProfile';
$route['api/v1/getEmployees']        = 'ApiController/PageController/getEmployees';
$route['api/v1/getShifts']           = 'ApiController/PageController/getShifts';
$route['api/v1/getEmployeePayments'] = 'ApiController/PageController/getEmployeePayments';


// Sales
$route['api/v1/getSaleInvoice'] = 'ApiController/SalesController/getSaleInvoice';
$route['api/v1/addOder']       = 'ApiController/SalesController/addOrder';
$route['api/v1/addSales']       = 'ApiController/SalesController/addSales';
$route['api/v1/updateSales']    = 'ApiController/SalesController/updateSales';
$route['api/v1/getSales']       = 'ApiController/SalesController/getSales';
$route['api/v1/deleteSales']    = 'ApiController/SalesController/deleteSales';
$route['api/v1/getSalesRecord'] = 'ApiController/SalesController/getSalesRecord';
$route['api/v1/getProfitLossProduct']  = 'ApiController/SalesController/getProfitLossFilters';
$route['api/v1/get_profit_loss']  = 'ApiController/SalesController/getProfitLoss';
$route['api/v1/getSaleSummary'] = 'ApiController/SalesController/getSaleSummary';
$route['api/v1/getSaleDetails'] = 'ApiController/SalesController/getSaleDetails';
$route['api/v1/getTopData']     = 'ApiController/SalesController/getTopData';
$route['api/v1/getGraphData']     = 'ApiController/SalesController/getGraphData';

// Customer 
$route['api/v1/getCustomerId']         = 'ApiController/CustomerController/getCustomerId';
$route['api/v1/addCustomer']           = 'ApiController/CustomerController/addCustomer';
$route['api/v1/updateCustomer']        = 'ApiController/CustomerController/updateCustomer';
$route['api/v1/deleteCustomer']        = 'ApiController/CustomerController/deleteCustomer';
$route['api/v1/getCustomers']          = 'ApiController/CustomerController/getCustomers';
$route['api/v1/getCustomerDue']        = 'ApiController/CustomerController/getCustomerDue';
$route['api/v1/getCustomerPayments']   = 'ApiController/CustomerController/getCustomerPayments';
$route['api/v1/addCustomerPayment']    = 'ApiController/CustomerController/addCustomerPayment';
$route['api/v1/updateCustomerPayment'] = 'ApiController/CustomerController/updateCustomerPayment';
$route['api/v1/deleteCustomerPayment'] = 'ApiController/CustomerController/deleteCustomerPayment';
$route['api/v1/getCustomerLedger']     = 'ApiController/CustomerController/getCustomerLedger';

// eployee wise customer
$route['api/v1/getEmployeeWiseCustomer']               = 'ApiController/CustomerController/getEmployeeWiseCustomer';

// Product
$route['api/v1/getProductCode']        = 'ApiController/ProductController/getProductCode';
$route['api/v1/addProduct']            = 'ApiController/ProductController/addProduct';
$route['api/v1/updateProduct']         = 'ApiController/ProductController/updateProduct';
$route['api/v1/deleteProduct']         = 'ApiController/ProductController/deleteProduct';
$route['api/v1/activeProduct']         = 'ApiController/ProductController/activeProduct';
$route['api/v1/getAllProducts']        = 'ApiController/ProductController/getProducts';
$route['api/v1/getCustomerProducts']   = 'ApiController/ProductController/getCustomerProducts';
$route['api/v1/getCustomerCategories'] = 'ApiController/ProductController/getCustomerCategories';
$route['api/v1/getProductStock']       = 'ApiController/ProductController/getProductStock';
$route['api/v1/getCurrentStock']       = 'ApiController/ProductController/getCurrentStock';
$route['api/v1/getTotalStock']         = 'ApiController/ProductController/getTotalStock';
$route['api/v1/viewAllProduct']        = 'ApiController/ProductController/view_all_product';
$route['api/v1/getProductLedger']      = 'ApiController/ProductController/getProductLedger';
// $route['api/v1/getBranchWiseStock']    = "ApiController/ProductController/getBranchWiseStock";
$route['api/v1/getProducts']           = 'ApiController/ProductController/getProductCategoryWise';
$route['api/v1/get_branch_wise_stock']           = 'ApiController/ProductController/getBranchWiseStock';
$route['api/v1/get_user_data']         = 'ApiController/PageController/getGraphData'; 

// Supplier
$route['api/v1/getSupplierId']         = 'ApiController/SupplierController/getSupplierId';
$route['api/v1/addSupplier']           = 'ApiController/SupplierController/addSupplier';
$route['api/v1/updateSupplier']        = 'ApiController/SupplierController/updateSupplier';
$route['api/v1/deleteSupplier']        = 'ApiController/SupplierController/deleteSupplier';
$route['api/v1/getSuppliers']          = 'ApiController/SupplierController/getSuppliers';
$route['api/v1/getSupplierDue']        = 'ApiController/SupplierController/getSupplierDue';
$route['api/v1/getSupplierLedger']     = 'ApiController/SupplierController/getSupplierLedger';
$route['api/v1/addSupplierPayment']    = 'ApiController/SupplierController/addSupplierPayment';
$route['api/v1/updateSupplierPayment'] = 'ApiController/SupplierController/updateSupplierPayment';
$route['api/v1/getSupplierPayments']   = 'ApiController/SupplierController/getSupplierPayments';
$route['api/v1/deleteSupplierPayment'] = 'ApiController/SupplierController/deleteSupplierPayment';

// Purchase
$route['api/v1/getPurchaseInvoice']         = 'ApiController/PurchaseController/getPurchaseInvoice';
$route['api/v1/addPurchase']                = 'ApiController/PurchaseController/addPurchase';
$route['api/v1/updatePurchase']             = 'ApiController/PurchaseController/updatePurchase';
$route['api/v1/getPurchases']               = 'ApiController/PurchaseController/getPurchases';
$route['api/v1/deletePurchase']             = 'ApiController/PurchaseController/deletePurchase';
$route['api/v1/getPurchaseRecord']          = 'ApiController/PurchaseController/getPurchaseRecord';
$route['api/v1/addMaterialPurchase']        = 'ApiController/PurchaseController/addMaterialPurchase';
$route['api/v1/updateMaterialPurchase']     = 'ApiController/PurchaseController/updateMaterialPurchase';
$route['api/v1/deleteMaterialPurchase']     = 'ApiController/PurchaseController/deleteMaterialPurchase';
$route['api/v1/getMaterialPurchase']        = 'ApiController/PurchaseController/getMaterialPurchase';
$route['api/v1/getMaterialPurchaseDetails'] = 'ApiController/PurchaseController/getMaterialPurchaseDetails';
$route['api/v1/getPurchaseDetails']         = 'ApiController/PurchaseController/getPurchaseDetails';

// Account
$route['api/v1/addCashTransaction']     = 'ApiController/AccountController/addCashTransaction';
$route['api/v1/updateCashTransaction']  = 'ApiController/AccountController/updateCashTransaction';
$route['api/v1/getCashTransactions']    = 'ApiController/AccountController/getCashTransactions';
$route['api/v1/deleteCashTransaction']  = 'ApiController/AccountController/deleteCashTransaction';
$route['api/v1/getCashTransactionCode'] = 'ApiController/AccountController/getCashTransactionCode';
$route['api/v1/getAccounts']            = 'ApiController/AccountController/getAccounts';
$route['api/v1/addBankTransaction']     = 'ApiController/AccountController/addBankTransaction';
$route['api/v1/updateBankTransaction']  = 'ApiController/AccountController/updateBankTransaction';
$route['api/v1/getBankTransactions']    = 'ApiController/AccountController/getBankTransactions';
$route['api/v1/getAllBankTransactions'] = 'ApiController/AccountController/getAllBankTransactions';
$route['api/v1/removeBankTransaction']  = 'ApiController/AccountController/removeBankTransaction';
$route['api/v1/getBank_Balance']        = 'ApiController/AccountController/getBankBalance';
$route['api/v1/getBankAccounts']        = 'ApiController/AccountController/getBankAccounts';
$route['api/v1/getOtherIncomeExpense']  = 'ApiController/AccountController/getOtherIncomeExpense';
$route['api/v1/cashTransactionSummery'] = 'ApiController/AccountController/cashTransactionSummery';
$route['api/v1/bankTransactionSummery'] = 'ApiController/AccountController/bankTransactionSummery';
$route['api/v1/getCashView']            = 'ApiController/AccountController/getCashView';
$route['api/v1/getBankLedger']          = 'ApiController/AccountController/getBankLedger';
$route['api/v1/getBalanceReport']       = 'ApiController/AccountController/getBalanceReport';


// Production
$route['api/v1/getMaterialInvoice']    = 'ApiController/ProductionController/getMaterialInvoice';
$route['api/v1/addProduction']         = 'ApiController/ProductionController/addProduction';
$route['api/v1/updateProduction']      = 'ApiController/ProductionController/updateProduction';
$route['api/v1/deleteProduction']      = 'ApiController/ProductionController/deleteProduction';
$route['api/v1/getProductions']        = 'ApiController/ProductionController/getProductions';
$route['api/v1/getProductionRecord']   = 'ApiController/ProductionController/getProductionRecord';
$route['api/v1/getProductionDetails']  = 'ApiController/ProductionController/getProductionDetails';
$route['api/v1/getProductionProducts'] = 'ApiController/ProductionController/getProductionProducts';
$route['api/v1/getMaterials']          = 'ApiController/ProductionController/getMaterials';
