/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$(document).ready(function() {mermaid.initialize({startOnLoad:true});
                          mermaid.ganttConfig = {axisFormatter: [['%d.%m.%Y', function (d){return d.getDay() == 1;}]]};});


