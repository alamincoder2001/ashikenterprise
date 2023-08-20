const salesInvoice = Vue.component("sales-invoice", {
  template: `
        <div>
            <div class="row">
                <div class="col-xs-12">
                    <a href="" v-on:click.prevent="print"><i class="fa fa-print"></i> Print</a>
                    <label for="english" style="margin-left: 10px;">
                        <input type="radio" id="english" value="english" v-model="language"> English
                    </label>
                    <label for="bangla" style="margin-left: 5px;">
                        <input type="radio" id="bangla" value="bangla" v-model="language"> Bangla
                    </label>
                </div>
            </div>
            
            <div id="invoiceContent">
                <div class="row">
                    <div class="col-xs-12 text-center">
                        <div _h098asdh>
                        {{ language == 'english' ? 'Sales Invoice' :  language == 'bangla' ? 'ক্যাশ মেমো' : '' }}
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-7">
                        <strong>{{ language == 'english' ? 'Customer Id' : language == 'bangla' ? 'গ্রাহক আইডি' : '' }}:</strong>  {{ sales.Customer_Code   }}<br>
                        <strong>{{ language == 'english' ? 'Customer Name' : language == 'bangla' ? 'গ্রাহকের নাম' : '' }}:</strong> {{ sales.Customer_Name }}<br>
                        <strong>{{ language == 'english' ? 'Customer Address' : language == 'bangla' ? 'গ্রাহকের ঠিকানা' : ''}}: </strong> {{ sales.Customer_Address }}<br>
                        <strong>{{ language == 'english' ? 'Customer Mobile' : language == 'bangla' ? 'মোবাইল নাম্বার' : ''}}:</strong> {{ sales.Customer_Mobile }}
                    </div>
                    <div class="col-xs-5 text-right">
                        <strong>{{ language == 'english' ? 'Sales by' : language == 'bangla' ? 'বিক্রয়কারী' : ''}}:</strong> {{ sales.AddBy }}<br>
                        <strong>{{ language == 'english' ? 'Invoice No.' : language == 'bangla' ? 'মেমো নাম্বার' : ""}}:</strong> {{ sales.SaleMaster_InvoiceNo }}<br> 
                        <strong>{{ language == 'english' ? 'Sales Date' : language == 'bangla' ? 'তারিখ' : ''}}: </strong> {{ sales.SaleMaster_SaleDate }} {{ sales.AddTime | formatDateTime('h:mm a') }} <br> 
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12">
                        <table _a584de>
                            <thead>
                                <tr>
                                    <td>{{ language == 'english' ? 'Sl.' : language == 'bangla' ? 'ক্রমিক নং' : ''}}</td>
                                    <td>{{ language == 'english' ? 'Description' : language == 'bangla' ? 'পন্যের বিবরণ' : ''}}</td>
                                    <td>{{ language == 'english' ? 'O. Qty' : language == 'bangla' ? 'অর্ডার পরিমান' : ''}}</td>
                                    <td>{{ language == 'english' ? 'O. Total' : language == 'bangla' ? 'অর্ডার মোট' : ''}}</td>
                                    <td>{{ language == 'english' ? 'S. Qty' : language == 'bangla' ? 'এস পরিমান' : ''}}</td>
                                    <td>{{ language == 'english' ? 'U. Price' : language == 'bangla' ? 'একক মূল্য' : ''}}</td>
                                    <td style="text-align:right;">{{ language == 'english' ? 'Total' : language == 'bangla' ? 'মোট মূল্য' : ''}}</td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(product, sl) in cart">
                                    <td>{{ sl + 1 }}</td>
                                    <td width="40%">{{ product.Product_Name }}</td>
                                    <td>{{ product.SaleDetails_TotalQuantity }}</td>
                                    <td>{{ product.SaleDetails_TotalAmount }}</td>
                                    <td>{{ product.Quantity }}</td>
                                    <td>{{ product.SaleDetails_Rate }}</td>
                                    <td align="right">{{ parseFloat(product.totalAmount).toFixed(2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-8">
                        <table _a584dee :style="{display: damage.length > 0 ? '' : 'none'}">
                            <thead>
                                <tr>
                                    <td colspan="4">Damage Record</td>
                                </tr>
                                <tr>
                                    <td>Sl.</td>
                                    <td>Description</td>
                                    <td>Qty</td>
                                    <td>Total</td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, sl) in damage">
                                    <td>{{ sl + 1 }}</td>
                                    <td>{{ item.Product_Name }}</td>
                                    <td>{{ item.quantity }}</td>
                                    <td>{{ item.total }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <br>
                        <table class="pull-left">
                            <tr>
                                <td><strong>{{ language == 'english' ? 'Previous Due' : language == 'bangla' ? 'পূর্বের বকেয়া' : ''}}:</strong></td>
                                
                                <td style="text-align:right">{{ sales.SaleMaster_Previous_Due == null ? '0.00' : sales.SaleMaster_Previous_Due  }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ language == 'english' ? 'Current Due' : language == 'bangla' ? 'বর্তমান বকেয়া' : ''}}:</strong></td>
                                
                                <td style="text-align:right">{{ sales.SaleMaster_DueAmount == null ? '0.00' : sales.SaleMaster_DueAmount  }}</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="border-bottom: 1px solid #ccc;"></td>
                            </tr>
                            <tr>
                                <td><strong>{{ language == 'english' ? 'Total Due' : language == 'bangla' ? 'মোট বকেয়া' : ''}}:</strong></td>
                                <td style="text-align:right">{{ (parseFloat(sales.SaleMaster_Previous_Due) + parseFloat(sales.SaleMaster_DueAmount == null ? 0.00 : sales.SaleMaster_DueAmount)).toFixed(2) }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-xs-4">
                        <table _t92sadbc2>
                            <tr>
                                <td style="text-align:right;width:50%;"><strong>{{ language == 'english' ? 'Sub Total' : language == 'bangla' ? 'মোট' : 'المجموع الفرعي'}}:</strong></td>
                                <td style="text-align:right">{{ (+sales.SaleMaster_SubTotalAmount + +sales.SaleMaster_ReturnTotal + +sales.SaleMaster_DamageTotal).toFixed(2) }}</td>
                            </tr>
                            <tr>
                                <td style="text-align:right;width:50%;"><strong>{{ language == 'english' ? 'Return Total(-)' : language == 'bangla' ? 'মোট রিটার্ন' : ''}}:</strong></td>
                                <td style="text-align:right">{{ sales.SaleMaster_ReturnTotal }}</td>
                            </tr>
                            <tr>
                                <td style="text-align:right;width:50%;"><strong>{{ language == 'english' ? 'Damage Total(-)' : language == 'bangla' ? 'মোট ড্যামেজ' : ''}}:</strong></td>
                                <td style="text-align:right">{{ sales.SaleMaster_DamageTotal }}</td>
                            </tr>
                            <tr>
                                <td style="text-align:right;width:50%;"><strong>{{ language == 'english' ? 'VAT' : language == 'bangla' ? 'ভ্যাট' : ''}}:</strong></td>
                                <td style="text-align:right">{{ sales.SaleMaster_TaxAmount }}</td>
                            </tr>
                            <tr>
                                <td style="text-align:right;width:50%;"><strong>{{ language == 'english' ? 'Discount' : language == 'bangla' ? 'ডিসকাউন্ট' : ''}}:</strong></td>
                                <td style="text-align:right">{{ sales.SaleMaster_TotalDiscountAmount }}</td>
                            </tr>
                            <tr>
                                <td style="text-align:right;width:50%;"><strong>{{ language == 'english' ? 'Transport Cost' : language == 'bangla' ? 'পরিবহন খরচ' : ''}}:</strong></td>
                                <td style="text-align:right">{{ sales.SaleMaster_Freight }}</td>
                            </tr>
                            <tr><td colspan="2" style="border-bottom: 1px solid #ccc"></td></tr>
                            <tr>
                                <td style="text-align:right;width:50%;"><strong>{{ language == 'english' ? 'Total' : language == 'bangla' ? 'সর্বমোট' : ''}}:</strong></td>
                                <td style="text-align:right">{{ sales.SaleMaster_TotalSaleAmount }}</td>
                            </tr>
                            <tr>
                                <td style="text-align:right;width:50%;"><strong>{{ language == 'english' ? 'Paid' : language == 'bangla' ? 'জমা' : ''}}:</strong></td>
                                <td style="text-align:right">{{ sales.SaleMaster_PaidAmount }}</td>
                            </tr>
                            <tr><td colspan="2" style="border-bottom: 1px solid #ccc"></td></tr>
                            <tr>
                                <td style="text-align:right;width:50%;"><strong>{{ language == 'english' ? 'Due' : language == 'bangla' ? 'বকেয়া' : ''}}:</strong></td>
                                <td style="text-align:right">{{ sales.SaleMaster_DueAmount }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12">
                        <strong>In Word: </strong> {{ convertNumberToWords(sales.SaleMaster_TotalSaleAmount) }}<br><br>
                        <strong>Note: </strong>
                        <p style="white-space: pre-line">{{ sales.SaleMaster_Description }}</p>
                    </div>
                </div>
            </div>
        </div>
    `,
  props: ["sales_id"],
  data() {
    return {
      sales: {
        SaleMaster_InvoiceNo: null,
        SalseCustomer_IDNo: null,
        SaleMaster_SaleDate: null,
        Customer_Name: null,
        Customer_Address: null,
        Customer_Mobile: null,
        SaleMaster_TotalSaleAmount: null,
        SaleMaster_TotalDiscountAmount: null,
        SaleMaster_TaxAmount: null,
        SaleMaster_Freight: null,
        SaleMaster_SubTotalAmount: null,
        SaleMaster_ReturnTotal: null,
        SaleMaster_DamageTotal: null,
        SaleMaster_PaidAmount: null,
        SaleMaster_DueAmount: null,
        SaleMaster_Previous_Due: null,
        SaleMaster_Description: null,
        AddBy: null,
      },
      cart: [],
      damage: [],
      style: null,
      language: "english",
      companyProfile: null,
      currentBranch: null,
    };
  },
  filters: {
    formatDateTime(dt, format) {
      return dt == "" || dt == null ? "" : moment(dt).format(format);
    },
  },
  created() {
    this.setStyle();
    this.getSales();
    this.getCurrentBranch();
  },
  methods: {
    getSales() {
      axios.post("/get_sales", { salesId: this.sales_id }).then((res) => {
        this.sales = res.data.sales[0];
        this.cart = res.data.saleDetails;
        this.damage = res.data.saleDamage;
      });
    },
    getCurrentBranch() {
      axios.get("/get_current_branch").then((res) => {
        this.currentBranch = res.data;
      });
    },
    setStyle() {
      this.style = document.createElement("style");
      this.style.innerHTML = `
                div[_h098asdh]{
                    background-color:#ffff0024;
                    font-weight: bold;
                    font-size:15px;
                    margin-bottom:15px;
                    border: 1px dotted #454545;
                    -webkit-print-color-adjust: exact;
                }
                div[_d9283dsc]{
                    padding-bottom: 10px;
                    border-bottom: 1px solid #ccc;
                    margin-bottom: 10px;
                }
                table[_a584de]{
                    width: 100%;
                    text-align:center;
                }
                table[_a584de] thead{
                    font-weight:bold;
                }
                table[_a584de] td{
                    padding: 1px;
                    border: 1px solid #ccc;
                }
                table[_a584dee]{
                    margin-top: 10px;
                    width: 100%;
                    text-align:center;
                }
                table[_a584dee] thead{
                    font-weight:bold;
                }
                table[_a584dee] td{
                    padding: 1px;
                    border: 1px solid #ccc;
                }
                table[_t92sadbc2]{
                    width: 100%;
                }
                table[_t92sadbc2] td{
                    padding: 2px;
                }
            `;
      document.head.appendChild(this.style);
    },
    convertNumberToWords(amountToWord) {
      var words = new Array();
      words[0] = "";
      words[1] =
        this.language == "english"
          ? "One"
          : this.language == "bangla"
          ? "এক"
          : "";
      words[2] =
        this.language == "english"
          ? "Two"
          : this.language == "bangla"
          ? "দুই"
          : "";
      words[3] =
        this.language == "english"
          ? "Three"
          : this.language == "bangla"
          ? "তিন"
          : "";
      words[4] =
        this.language == "english"
          ? "Four"
          : this.language == "bangla"
          ? "চার"
          : "";
      words[5] =
        this.language == "english"
          ? "Five"
          : this.language == "bangla"
          ? "পাঁচ"
          : "";
      words[6] =
        this.language == "english"
          ? "Six"
          : this.language == "bangla"
          ? "ছয়"
          : "";
      words[7] =
        this.language == "english"
          ? "Seven"
          : this.language == "bangla"
          ? "সাত"
          : "";
      words[8] =
        this.language == "english"
          ? "Eight"
          : this.language == "bangla"
          ? "আট"
          : "";
      words[9] =
        this.language == "english"
          ? "Nine"
          : this.language == "bangla"
          ? "নয়"
          : "";
      words[10] =
        this.language == "english"
          ? "Ten"
          : this.language == "bangla"
          ? "দশ"
          : "";
      words[11] =
        this.language == "english"
          ? "Eleven"
          : this.language == "bangla"
          ? "এগারো"
          : "";
      words[12] =
        this.language == "english"
          ? "Twelve"
          : this.language == "bangla"
          ? "বারো"
          : "";
      words[13] =
        this.language == "english"
          ? "Thirteen"
          : this.language == "bangla"
          ? "তেরো"
          : "";
      words[14] =
        this.language == "english"
          ? "Fourteen"
          : this.language == "bangla"
          ? "চৌদ্দ"
          : "";
      words[15] =
        this.language == "english"
          ? "Fifteen"
          : this.language == "bangla"
          ? "পনেরো"
          : "";
      words[16] =
        this.language == "english"
          ? "Sixteen"
          : this.language == "bangla"
          ? "ষোলো"
          : "";
      words[17] =
        this.language == "english"
          ? "Seventeen"
          : this.language == "bangla"
          ? "সতেরো"
          : "";
      words[18] =
        this.language == "english"
          ? "Eighteen"
          : this.language == "bangla"
          ? "আঠারো"
          : "";
      words[19] =
        this.language == "english"
          ? "Nineteen"
          : this.language == "bangla"
          ? "উনিশ"
          : "";
      words[20] =
        this.language == "english"
          ? "Twenty"
          : this.language == "bangla"
          ? "বিশ"
          : "";
      words[30] =
        this.language == "english"
          ? "Thirty"
          : this.language == "bangla"
          ? "ত্রিশ"
          : "";
      words[40] =
        this.language == "english"
          ? "Forty"
          : this.language == "bangla"
          ? "চল্লিশ"
          : "";
      words[50] =
        this.language == "english"
          ? "Fifty"
          : this.language == "bangla"
          ? "পঞ্চাশ"
          : "";
      words[60] =
        this.language == "english"
          ? "Sixty"
          : this.language == "bangla"
          ? "ষাইট"
          : "";
      words[70] =
        this.language == "english"
          ? "Seventy"
          : this.language == "bangla"
          ? "সত্তর"
          : "";
      words[80] =
        this.language == "english"
          ? "Eighty"
          : this.language == "bangla"
          ? "আশি"
          : "";
      words[90] =
        this.language == "english"
          ? "Ninety"
          : this.language == "bangla"
          ? "নব্বই"
          : "";
      amount = amountToWord == null ? "0.00" : amountToWord.toString();
      var atemp = amount.split(".");
      var number = atemp[0].split(",").join("");
      var n_length = number.length;
      var words_string = "";
      if (n_length <= 9) {
        var n_array = new Array(0, 0, 0, 0, 0, 0, 0, 0, 0);
        var received_n_array = new Array();
        for (var i = 0; i < n_length; i++) {
          received_n_array[i] = number.substr(i, 1);
        }
        for (var i = 9 - n_length, j = 0; i < 9; i++, j++) {
          n_array[i] = received_n_array[j];
        }
        for (var i = 0, j = 1; i < 9; i++, j++) {
          if (i == 0 || i == 2 || i == 4 || i == 7) {
            if (n_array[i] == 1) {
              n_array[j] = 10 + parseInt(n_array[j]);
              n_array[i] = 0;
            }
          }
        }
        value = "";
        for (var i = 0; i < 9; i++) {
          if (i == 0 || i == 2 || i == 4 || i == 7) {
            value = n_array[i] * 10;
          } else {
            value = n_array[i];
          }
          if (value != 0) {
            words_string += words[value] + " ";
          }
          if (
            (i == 1 && value != 0) ||
            (i == 0 && value != 0 && n_array[i + 1] == 0)
          ) {
            words_string +=
              this.language == "english"
                ? "Crores "
                : this.language == "bangla"
                ? " কোটি "
                : "";
          }
          if (
            (i == 3 && value != 0) ||
            (i == 2 && value != 0 && n_array[i + 1] == 0)
          ) {
            words_string +=
              this.language == "english"
                ? "Lakhs "
                : this.language == "bangla"
                ? " লক্ষ "
                : "";
          }
          if (
            (i == 5 && value != 0) ||
            (i == 4 && value != 0 && n_array[i + 1] == 0)
          ) {
            words_string +=
              this.language == "english"
                ? "Thousand "
                : this.language == "bangla"
                ? " হাজার "
                : "";
          }
          if (
            i == 6 &&
            value != 0 &&
            n_array[i + 1] != 0 &&
            n_array[i + 2] != 0
          ) {
            words_string +=
              this.language == "english"
                ? "Hundred and "
                : this.language == "bangla"
                ? " শত এবং "
                : "";
          } else if (i == 6 && value != 0) {
            words_string +=
              this.language == "english"
                ? "Hundred "
                : this.language == "bangla"
                ? " শত "
                : "";
          }
        }
        words_string = words_string.split("  ").join(" ");
      }
      return (
        words_string +
        (this.language == "english"
          ? " only"
          : this.language == "bangla"
          ? " টাকা মাত্র"
          : "")
      );
    },
    async print() {
      let invoiceContent = document.querySelector("#invoiceContent").innerHTML;
      let printWindow = window.open(
        "",
        "PRINT",
        `width=${screen.width}, height=${screen.height}, left=0, top=0`
      );
      if (this.currentBranch.print_type == "3") {
        printWindow.document.write(`
                    <html>
                        <head>
                            <title>Invoice</title>
                            <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
                            <style>
                                body, table{
                                    font-size:11px;
                                }
                            </style>
                        </head>
                        <body>
                            <div style="text-align:center;">
                                <img src="/uploads/company_profile_thum/${this.currentBranch.Company_Logo_org}" alt="Logo" style="height:80px;margin:0px;" /><br>
                                <strong style="font-size:18px;">${this.currentBranch.Company_Name}</strong><br>
                                <p style="white-space:pre-line;">${this.currentBranch.Repot_Heading}</p>
                            </div>
                            ${invoiceContent}
                        </body>
                    </html>
                `);
      } else if (this.currentBranch.print_type == "2") {
        printWindow.document.write(`
                    <!DOCTYPE html>
                    <html lang="en">
                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <meta http-equiv="X-UA-Compatible" content="ie=edge">
                        <title>Invoice</title>
                        <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
                        <style>
                            html, body{
                                width:500px!important;
                            }
                            body, table{
                                font-size: 13px;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="row">
                            <div class="col-xs-2"><img src="/uploads/company_profile_thum/${this.currentBranch.Company_Logo_org}" alt="Logo" style="height:80px;" /></div>
                            <div class="col-xs-10" style="padding-top:20px;">
                                <strong style="font-size:18px;">${this.currentBranch.Company_Name}</strong><br>
                                <p style="white-space:pre-line;">${this.currentBranch.Repot_Heading}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <div style="border-bottom: 4px double #454545;margin-top:7px;margin-bottom:7px;"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                ${invoiceContent}
                            </div>
                        </div>
                    </body>
                    </html>
				`);
      } else {
        printWindow.document.write(`
                    <!DOCTYPE html>
                    <html lang="en">
                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <meta http-equiv="X-UA-Compatible" content="ie=edge">
                        <title>Invoice</title>
                        <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
                        <style>
                            body, table{
                                font-size: 13px;
                            }
                            @media print {
                                div[_h098asdh] {
                                    background-color:#ffff0024 !important;
                                    -webkit-print-color-adjust: exact;
                                }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <table style="width:100%;">
                                <thead>
                                    <tr>
                                        <td>
                                            <div class="row">
                                                <div class="col-xs-2"><img src="/uploads/company_profile_thum/${
                                                  this.currentBranch
                                                    .Company_Logo_org
                                                }" alt="Logo" style="height:80px;" /></div>
                                                <div class="col-xs-10" style="padding-top:20px;">
                                                    <strong style="font-size:18px;">${
                                                      this.currentBranch
                                                        .Company_Name
                                                    }</strong><br>
                                                    <p style="white-space:pre-line;">${
                                                      this.currentBranch
                                                        .Repot_Heading
                                                    }</p>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-xs-12">
                                                    <div style="border-bottom: 4px double #454545;margin-top:7px;margin-bottom:7px;"></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="row">
                                                <div class="col-xs-12">
                                                    ${invoiceContent}
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>
                                            <div style="width:100%;height:50px;">&nbsp;</div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                            
                            <div style="position:fixed;left:0;bottom:15px;width:100%;">
                                <div class="row" style="border-bottom:1px solid #ccc;margin-bottom:5px;padding-bottom:6px;">
                                    <div class="col-xs-6">
                                        <span style="text-decoration:overline;">Received by</span>
                                    </div>
                                    <div class="col-xs-6 text-right">
                                        <span style="text-decoration:overline;">Authorized by</span>
                                    </div>
                                </div>
                                <div class="row" style="font-size:12px;">
                                    <div class="col-xs-6">
                                        Print Date: ${moment().format(
                                          "DD-MM-YYYY h:mm a"
                                        )}, Printed by: ${this.sales.AddBy}
                                    </div>
                                    <div class="col-xs-6 text-right">
                                        Developed by: Link-Up Technologoy, Contact no: 01911978897
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </body>
                    </html>
				`);
      }
      let invoiceStyle = printWindow.document.createElement("style");
      invoiceStyle.innerHTML = this.style.innerHTML;
      printWindow.document.head.appendChild(invoiceStyle);
      printWindow.moveTo(0, 0);

      printWindow.focus();
      await new Promise((resolve) => setTimeout(resolve, 1000));
      printWindow.print();
      printWindow.close();
    },
  },
});
