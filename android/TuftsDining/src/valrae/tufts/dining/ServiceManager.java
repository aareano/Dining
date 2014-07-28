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
	private final String GENERIC_URL = "http://hellobiped.com/tufts_dining/";
	private final String[] URLS;

	private final String POST = HttpPost.METHOD_NAME;
	private final String GET = HttpGet.METHOD_NAME;
	
	// of Activity
	private final Context CONTEXT;
		
	private final String TAG = "ServiceManager";
	
	private static String mJson;
	
	private static String mPath;
	
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
            
			if (target != null && serviceListener != null) {
				switch (msg.what) {
				case 0:	// error		// TODO as of yet, unused
						target.serviceError("Error in ServiceManager");
					break;
				case 1:	// successful response
						target.serviceAlert(mJson, mPath);
					break;
				default: // error
						target.serviceError("Error in ServiceManager");
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
	public ServiceManager (Context context) {
		Log.i(TAG, "new ServiceManager()");
		
		this.CONTEXT = context;
		this.URLS = CONTEXT.getResources().getStringArray(R.array.url_array);
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
	
	/**
	 * 
	 * @param data
	 */
	public void startService (final List<NameValuePair> data, String path) {
		mPath = path;
		data.add(new BasicNameValuePair(
				CONTEXT.getResources().getString(R.string.mac_key), getMac()));
		Log.i(TAG, "data in startService: " + data.toString());
		
		// Manage dialog
		String message = "Frolicking in data...";
		for (int i = 0; i < URLS.length; i++) {
			if (path.equals(URLS[i])) {
				if (i == 0 || i == 1 || i == 2) {			// create paths
					message = "Spreading your fame...";
				} else if (i == 3 || i == 7) {				// vote paths
					message = "Making your voice heard...";
				} else if (i == 4 || i == 5 || i == 6 
						|| i == 8 || i == 9 || i == 10) {	// tally paths
					message = "Fetching numbers";
				}
			}
		}
		manageDialog(message);
		
		Thread thread = new Thread()
		{
			@Override
			public void run() {

				mJson = makeServiceCall(GENERIC_URL + mPath, POST, data);
				Log.d("ServiceManager", mPath + " response > " + mJson);
				handler.sendEmptyMessage(1);	// TODO

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
					serviceCancel();
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
	
	public void serviceAlert (String json, String mPath) {
		serviceListener.onServiceComplete(json, mPath);
	}
	
	public void serviceCancel() {
		serviceListener.onCancel();
	}
	
	public void serviceError (String error) {	// TODO as of yet, unused
		serviceListener.onError(error);
	}
	
	/* -------------------------- Getter methods -------------------------- */
	
	// get JSON results
	public String getJson() {
		return mJson;
	}
}