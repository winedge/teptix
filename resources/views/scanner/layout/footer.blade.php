<footer class="main-footer">
    <div class="footer-left">
    {{\App\Models\Setting::find(1)->footertext}}<br>
    {{\App\Models\Setting::find(1)->developed_by}}
    </div>
    <div class="footer-right">
      {{\App\Models\Setting::find(1)->app_version}}
    </div>
</footer>
