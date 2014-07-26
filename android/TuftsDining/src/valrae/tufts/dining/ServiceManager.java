package valrae.tufts.dining;

import java.io.IOException;
import java.io.UnsupportedEncodingException;
import java.lang.ref.WeakReference;
import java.util.List;

import org.apache.http.HttpEntity;
import org.apache.http.HttpResponse;
import org.apache.http.NameValuePair;
import org.apache.http.client.ClientProtocolException;
import org.apache.http.client.entity.UrlEncodedFormEntity;
import org.apache.http.client.methods.HttpGet;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.client.utils.URLEncodedUtils;
import org.apache.http.impl.client.DefaultHttpClient;
import org.apache.http.message.BasicNameValuePair;
import org.apache.http.util.EntityUtils;

import android.app.ProgressDialog;
import android.content.Context;
import android.content.DialogInterface;
import android.content.DialogInterface.OnCancelListener;
import android.net.ConnectivityManager;
import android.net.NetworkInfo;
import android.net.wifi.WifiInfo;
import android.net.wifi.WifiManager;
import android.os.Handler;
import android.os.Message;
import android.util.Log;

public class ServiceManager {
	
	// HTTP call URLs
	private final String VOTE_URL 			= "http://hellobiped.com/tufts_dining/vote";
	private final String TALLY_VOTES_URL 	= "http://hellobiped.com/tufts_dining/tally_votes";
	private final String RATE_URL 			= "http://hellobiped.com/tufts_dining/rate";
	private final String TALLY_RATINGS_URL 	= "http://hellobiped.com/tufts_dining/tally_ratings";

	public final static String GET  = HttpGet.METHOD_NAME;
	public final static String POST = HttpPost.METHOD_NAME;
	
	// of Activity
	private final Context CONTEXT;

	// Functionality of Activity
	private final String FUNCTIONALITY;
	private final String COMPARISON;
	private final String RATING;
	
	private final String TAG = "ServiceManager";
	
	private static String mPostJson;
	private static String mGetJson;
	
	private NetworkInfo networkInfo;
	private ProgressDialog pDialog;
	
	private static ServiceListener serviceListener;
	private Handler handler;
	
	private static class myHandler extends Handler {
		private final WeakReference<ServiceManager> mTarget;
		
		public myHandler (ServiceManager target) {
			mTarget = new WeakReference<ServiceManager>(target);
		}
		
		@Override
		public void handleMessage(Message msg) {
			ServiceManager target = mTarget.get(); 
            
			if (target != null) {
				switch (msg.what) {
				case 0:
					if (serviceListener != null)
						target.alertFragment(mJson);
					break;
				case 1:
					if (serviceListener != null)
						target.alertFragment(mJson);
					break;
				default:
					break;
				}
            }
		}
	};	
	
	/**
	 * Creates new ServiceManager object
	 * @param context of activity that this is working in
	 * @param ServiceListener 
	 * @param function is functionality of the activity that is using ServiceManager
	 */
	public ServiceManager (Context context, String functionality) {
		Log.i(TAG, "new ServiceManager()");
		
		this.CONTEXT = context;
		this.FUNCTIONALITY = functionality;
		this.COMPARISON = ComparisonFragment.FUNCTIONALITY;
		this.RATING = RatingFragment.FUNCTIONALITY;
		this.handler = new myHandler(this);
	}

	/**
	 * Checks for network connection
	 * @return whether or not device has connection
	 */
	public boolean isConnected () {
		
		ConnectivityManager connMgr = (ConnectivityManager) 
				CONTEXT.getSystemService(Context.CONNECTIVITY_SERVICE);
		networkInfo = connMgr.getActiveNetworkInfo();
		
		// check network status
		if (networkInfo != null && networkInfo.isConnected()) {
			return true;
		} else {
			Log.e(TAG, "No network connection available.");
			return false;
		}
	}
	
	public void postVote(final List<NameValuePair> data) {
		data.add(new BasicNameValuePair(
				CONTEXT.getResources().getString(R.string.mac_key), 
				getMac()));
		Log.i(TAG, "data in startService: " + data.toString());
		
		String message = "Making your voice heard...";
		manageDialog(message);
		
		Thread thread = new Thread()
		{
			@Override
			public void run() {
				// make POST >> returns JSON response
				if (FUNCTIONALITY.equals(COMPARISON)) {
					mPostJson = makeServiceCall(VOTE_URL, POST, data);
					Log.d("ServiceManager", FUNCTIONALITY + " POST response > " + mPostJson);
				
				} else if (FUNCTIONALITY.equals(RATING)) {
					mPostJson = makeServiceCall(RATE_URL, POST, data);
					Log.d("ServiceManager", FUNCTIONALITY + " POST response > " + mPostJson);
				}
				handler.sendEmptyMessage(0);

				// TODO
//				handler.post(new Runnable() {
//					@Override
//					public void run() {
//						progressBar.setProgress(prog);
//					}
//				});
			}				
		};
		thread.start();
		try {
			thread.join();
		} catch (InterruptedException e) {
			e.printStackTrace();
		}
	}
	
	public void getTally() {
		String message = "Fetching numbers...";
		manageDialog(message);

		Thread thread = new Thread()
		{
			@Override
			public void run() {
				// make GET >> returns JSON response
				if (FUNCTIONALITY.equals(COMPARISON)) {									
					mGetJson = makeServiceCall(TALLY_VOTES_URL, GET);
					Log.d("ServiceManager", FUNCTIONALITY + " GET response > " + mGetJson);
				
				} else if (FUNCTIONALITY.equals(RATING)) {
					mGetJson = makeServiceCall(TALLY_RATINGS_URL, GET);
					Log.d("ServiceManager", FUNCTIONALITY + " GET response > " + mGetJson);
				}
				handler.sendEmptyMessage(1);
			}	   
		};
		thread.start();
		try {
			thread.join();
		} catch (InterruptedException e) {
			e.printStackTrace();
		}
	}
	
	/* ------------------------- Dialog methods ------------------------- */
	
	/**
	 * Ensures a ProgressDialog is showing and shows message
	 * @param message to show on ProgressDialog
	 */
	public void manageDialog (String message) {
		if (pDialog == null) {
			pDialog = new ProgressDialog(CONTEXT);
			pDialog.setOnCancelListener(new OnCancelListener() {

				@Override
				public void onCancel(DialogInterface arg0) {
					Log.i(TAG, "ProgressDialog canceled");
					cancelAlert();
				}
				
			});
		}
		pDialog.setMessage(message);
		if (!pDialog.isShowing()) {
			pDialog.setCancelable(true);
			pDialog.setCanceledOnTouchOutside(true);
			pDialog.show();
		}
	}
	
	/**
	 * Closes an open ProgressDialog
	 */
	public void close() {
		if (pDialog != null && pDialog.isShowing()) {
			pDialog.dismiss();
		}
	}

	public String getMac() {
		
		WifiManager manager = (WifiManager) CONTEXT.getSystemService(Context.WIFI_SERVICE);
		String address = null;
		
		if(manager.isWifiEnabled()) {

			// WIFI ALREADY ENABLED. GRAB THE MAC ADDRESS HERE
			WifiInfo info = manager.getConnectionInfo();
			address = info.getMacAddress();
			
		} else {
			// ENABLE THE WIFI FIRST
			manager.setWifiEnabled(true);
			
			// WIFI IS NOW ENABLED. GRAB THE MAC ADDRESS HERE
			WifiInfo info = manager.getConnectionInfo();
			address = info.getMacAddress();
		}
		return address; 
	}
	
	/* ------------------------- Service methods ------------------------- */
	
	/**
	 * Making service call
	 * @url - URL to make request
	 * @method - HTTP request method
	 */
	public String makeServiceCall(String url, String method) {
		return this.makeServiceCall(url, method, null);
	}
	
	/**
	 * Making service call
	 * @url - URL to make request
	 * @method - HTTP request method
	 * @params - HTTP request params
	 * */
	public String makeServiceCall(String url, String method, List<NameValuePair> params) {
		
		String response =  null;
		
		try {
			// HTTP client
			DefaultHttpClient httpClient = new DefaultHttpClient();
			HttpEntity httpEntity = null;
			HttpResponse httpResponse = null;
			 
			// check method >> POST
			if (method == POST) {
				HttpPost httpPost = new HttpPost(url);
				
				// adding params to POST
				if (params != null) {
					httpPost.setEntity(new UrlEncodedFormEntity(params));
				}			   
				// make request, get response
				httpResponse = httpClient.execute(httpPost);
 
			// check method >> GET
			} else if (method == GET) {
				
				// appending params to URL
				if (params != null) {				// this won't actually ever be used
					String paramString = URLEncodedUtils.format(params, "utf-8");
					url += "?" + paramString;		// how does this comes out?
				}
				HttpGet httpGet = new HttpGet(url);
				
				// make request, get response
				httpResponse = httpClient.execute(httpGet);
			}
			
			// convert httpEntity to String and return
			httpEntity = httpResponse.getEntity();
			response = EntityUtils.toString(httpEntity);
 
		} catch (UnsupportedEncodingException e) {
			e.printStackTrace();
		} catch (ClientProtocolException e) {
			e.printStackTrace();
		} catch (IOException e) {
			e.printStackTrace();
		}
		return response;
	}
	
	/* -------------------------- Callback method -------------------------- */
	public void setServiceListener(ServiceListener listener) {
		serviceListener = listener;
	}
	
	public void alertFragment(String method, String json) {
		serviceListener.onServiceComplete(method, json);
	}
	
	public void cancelAlert() {
		serviceListener.onCancel();
	}
	
	/* -------------------------- Getter methods -------------------------- */
	
	// get POST results
	public String getPOSTJson() {
		return mPostJson;
	}
	
	// get GET results
	public String getGETJson() {
		return mGetJson;
	}
}