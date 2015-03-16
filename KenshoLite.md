# Syntax #
A VALIDATION should be<br>
FIELDVALIDATION<code>[</code>, FIELDVALIDATION...<code>]</code>

A FIELDVALIDATION should be<br>
{<i>name</i>|<i>index</i>}:<i>DATATYPE</i><code>[</code>(<i>SUBVALIDATION</i>)<code>]</code><br>
<code>[</code>REQUIRED<code>]</code><br>
<code>[</code>ON {ERROR|MISSING} {REJECT|BREAK|SET <i>value</i>}<code>]</code><br>

Available <i>DATATYPE</i> is<br>
<ul><li>string<br>
<ul><li>email<br>
</li><li>url<br>
</li><li>date<br>
</li></ul></li><li>number<br>
<ul><li>integer<br>
<ul><li>dec<br>
</li><li>hex<br>
</li><li>oct<br>
</li><li>bin<br>
</li></ul></li><li>float<br>
</li></ul></li><li>boolean<br>
</li><li>array<br>
</li><li>index<br>
Available <i>SUBVALIDATION</i> is<br>
<b>string</b> a reg-ex pattern<br>
example: <code>string('/^\d{2}\w+$/')</code><br>
<b>date</b>   date format<br>
example: date('Y-m-d')<br>
<b>number</b> and all numeric types allow comma separated comparison expression<br>
example1: integer(>18, <90, !=35)<br>
example2: float(>3.1, <6.6)<br>
example3: integer(in (3,6,9, 12,15,18))<br>
<b>array</b> and <b>index</b> use VALIDATION query as its <i>SUBVALIDATION</i><br>
example: <code>array( subkey:string('/\s+/') on error set '',subkey2:integer(&lt;18, &gt;=0) required )</code>