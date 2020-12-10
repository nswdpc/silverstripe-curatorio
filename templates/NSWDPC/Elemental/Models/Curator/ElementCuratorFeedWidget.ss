<div class="content-element__content<% if $StyleVariant %> {$StyleVariant}<% end_if %>">
    <% include ElementCuratorFeedWidgetTitle %>
    <% if $CuratorFeedDescription %>
        <div class="nsw-instagram-feed__description">
            <p>$CuratorFeedDescription.XML</p>
        </div>
    <% end_if %>
    <div id="{$CuratorContainerId.XML}">
        <% if $IncludePoweredBy %><a href="https://curator.io" target="_blank" class="crt-logo crt-tag">Powered by Curator.io</a><% end_if %>
    </div>
</div>
