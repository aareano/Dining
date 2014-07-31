package valrae.tufts.dining;

import java.io.IOException;
import java.io.UnsupportedEncodingException;
import java.lang.ref.WeakReference;
import java.net.InetAddress;
import java.net.NetworkInterface;
import java.util.Enumeration;
import java.util.List;
import java.util.concurrent.Callable;
import java.util.concurrent.ExecutionException;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.concurrent.Future;
import java.util.concurrent.TimeUnit;
import java.util.concurrent.TimeoutException;

import org.apache.http.HttpEntity;
import org.apache.http.HttpResponse;
import org.apache.http.NameValuePair;
import org.apache.http.client.ClientProtocolException;
import org.apache.http.client.entity.UrlEncodedFormEntity;
import org.apache.http.client.methods.HttpGet;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.client.utils.URLEncodedUtils;
import org.apache.http.conn.util.InetAddressUtils;
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
	private final String[] PATHS;

	private final String POST = HttpPost.METHOD_NAME;
	private final String GET = HttpGet.METHOD_NAME;
	
	// of Activity
	private final Context CONTEXT;
		
	private final String TAG = "ServiceManager";
	
	private static String mJson;
	
	// used on worker thread
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
            Log.d("handler", "handleMessage()");
			
			if (target != null && serviceListener != null) {
				switch (msg.what) {
				case 0:	// error 
					target.serviceError("Error in ServiceManager");
					break;
				case 1:	// successful response
						target.serviceAlert(mJson);
					break;
				default: // TODO this will never be called
						target.serviceError("Error in ServiceManager");
					break;
				}
            } else {
            	Log.d("handleMessage", "target or serviceListener is null");
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
		this.PATHS = CONTEXT.getResources().getStringArray(R.array.paths_array);
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
	 * Ensures Http parameters are in order
	 * Shows appropriate progress dialog
	 * Makes Http request on worker thread with a Future<String>
	 * @param List<NameValuePair> data are Http params
	 */
	public void startService (final List<NameValuePair> data, String path) {
		mPath = path;
		
		if (path == CONTEXT.getResources().getStringArray(R.array.paths_array)[0]) {	// /create/user
			data.add(new BasicNameValuePair(CONTEXT.getResources().getString(R.string.ipv4_key), getIpAddress("ipv4")));
			data.add(new BasicNameValuePair(CONTEXT.getResources().getString(R.string.ipv6_key), getIpAddress("ipv6")));
		}
		data.add(new BasicNameValuePair(CONTEXT.getResources().getString(R.string.mac_key), getMac()));
		Log.i(TAG, "data in startService(): " + data.toString());
		
		// Manage dialog
		String message = "Frolicking in data...";
		for (int i = 0; i < PATHS.length; i++) {
			if (path.equals(PATHS[i])) {
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
		
		class Task implements Callable<String> {
			Task() {
			}
			@Override
			public String call() throws Exception {
				Log.d(TAG, "call(), mJson = " + mJson);
				
				Thread thread = new Thread()
				{
					@Override
					public void run() {
		
						mJson = makeServiceCall(GENERIC_URL + mPath, POST, data);
						Log.d("ServiceManager", mPath + " response > " + mJson);
						handler.sendEmptyMessage(1);
						Log.d(TAG, "after handler call");
		
						// TODO
		//				handler.post(new Runnable() {
		//					@Override
		//					public void run() {
		//						progressBar.setProgress(prog);
		//					}
		//				});
					}
				};
				mJson = null;		// reset mJson
				thread.start();		// TODO figure out where the wait happens
				try {
					thread.join();
				} catch (InterruptedException e) {
					e.printStackTrace();
				}
				return mJson;
			}
		}		// TODO currently working here :)
		
		ExecutorService executor = Executors.newSingleThreadExecutor();
		Future<String> future = executor.submit(new Task());
		try {
			future.get(3, TimeUnit.SECONDS);	// waits at most 3 seconds for task completion
		} catch (TimeoutException e) {
			e.printStackTrace();
			executor.shutdownNow();				// at 3 seconds, forces Task to shut down
			handler.sendEmptyMessage(0);		// sends error message to handler
		} catch (InterruptedException e) {
			e.printStackTrace();
		} catch (ExecutionException e) {
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
	
	/**
	 * Loops through all elements of all network interfaces to find ipaddress
	 * @param format is "ipv4" or "ipv6"
	 * @return ip address in requested format or null
	 */
	public String getIpAddress(String format) {
	    try {
	        for (Enumeration<NetworkInterface> en = NetworkInterface.getNetworkInterfaces(); en.hasMoreElements();) {
	            NetworkInterface intf = en.nextElement();
	            for (Enumeration<InetAddress> enumIpAddr = intf.getInetAddresses(); enumIpAddr.hasMoreElements();) {
	                InetAddress inetAddress = enumIpAddr.nextElement();
	                String ipAddr = inetAddress.getHostAddress();
	                
	                if (format == "ipv4") {
		                if (!inetAddress.isLoopbackAddress() && InetAddressUtils.isIPv4Address(ipAddr)) {
		                    return ipAddr;
		                } else
		                	return null;
	                } else if (format == "ipv6") {
	                	if (!inetAddress.isLoopbackAddress() && InetAddressUtils.isIPv6Address(ipAddr)) {
		                    return ipAddr;
		                } else
		                	return null;
	                }
	            }
	        }
	    } catch (Exception ex) {
	        Log.e("IP Address", ex.toString());
	    }
	    return null;
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
				
				// encode those params!
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
	
	public void serviceAlert (String json) {
		Log.d(TAG, "serviceAlert()");
		serviceListener.onServiceComplete(json);
		mJson = null;
	}
	
	public void serviceCancel() {
		serviceListener.onCancel();
		mJson = null;
	}
	
	public void serviceError (String error) {
		serviceListener.onError(error);
		mJson = null;
	}
	
	/* -------------------------- Getter methods -------------------------- */
	
	// get JSON results
	public String getJson() {
		return mJson;
	}
}